<?php

namespace App\Http\Controllers;

use App\Models\PrestamoInterno;
use App\Models\PrestamoInternoDevolucion;
use App\Models\CuentaEmpresa;
use App\Models\Empresa;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class PrestamoInternoController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $codigo = trim((string) $request->input('codigo'));
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $cuentaDestinoId = $request->input('cuenta_destino_id');
        $estado = $request->input('estado');

        $query = PrestamoInterno::with(['cuentaOrigen.empresa', 'cuentaDestino.empresa', 'devoluciones'])
            ->orderByDesc('fecha_prestamo');

        if ($codigo !== '') {
            $query->where('codigo_seguimiento', 'like', "%{$codigo}%");
        }
        if ($desde) {
            $query->whereDate('fecha_prestamo', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha_prestamo', '<=', $hasta);
        }
        if ($cuentaDestinoId) {
            $query->where('cuenta_destino_id', $cuentaDestinoId);
        }
        if (in_array($estado, ['pendiente', 'pagado_parcial', 'pagado'])) {
            $query->where('estado', $estado);
        }

        $prestamos = $query->paginate(20)->withQueryString();

        $empresas = Empresa::with('cuentas')->get();
        $cuentas = CuentaEmpresa::whereNull('deleted_at')->orderBy('nombre_cuenta')->get();
        $idempotencyToken = $this->generarToken('prestamo_store_token');

        return view('prestamos_internos.index', compact(
            'prestamos', 'empresas', 'cuentas', 'idempotencyToken',
            'codigo', 'desde', 'hasta', 'cuentaDestinoId', 'estado'
        ));
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('prestamo_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('prestamos_internos.index');
        }
        $request->validate([
            'cuenta_origen_id'   => 'required|exists:cuentas_empresa,id',
            'cuenta_destino_id'  => 'required|exists:cuentas_empresa,id|different:cuenta_origen_id',
            'monto'              => 'required|numeric|min:0.01',
            'moneda'             => 'required|string|max:10',
            'fecha_prestamo'     => 'required|date',
            'fecha_vencimiento'  => 'nullable|date|after:fecha_prestamo',
            'concepto'           => 'nullable|string',
            'metodo_pago'        => 'nullable|in:TRANSFERENCIA,QR',
            'codigo_seguimiento' => 'required_if:metodo_pago,TRANSFERENCIA|nullable|string|max:100',
            'voucher'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'codigo_seguimiento.required_if' => 'El código de la transferencia es obligatorio.',
            'voucher.mimes'                  => 'El comprobante debe ser JPG, PNG o PDF.',
            'voucher.max'                    => 'El comprobante no debe superar los 5 MB.',
        ]);

        DB::transaction(function () use ($request) {
            $prestamo = PrestamoInterno::create([
                'cuenta_origen_id'   => $request->cuenta_origen_id,
                'cuenta_destino_id'  => $request->cuenta_destino_id,
                'monto_original'     => $request->monto,
                'monto_devuelto'     => 0,
                'moneda'             => $request->moneda,
                'fecha_prestamo'     => $request->fecha_prestamo,
                'fecha_vencimiento'  => $request->fecha_vencimiento,
                'estado'             => 'pendiente',
                'concepto'           => $request->concepto,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $this->resolverCodigoSeguimiento($request->metodo_pago, $request->codigo_seguimiento),
                'voucher'            => $request->hasFile('voucher') ? $request->file('voucher')->store('vouchers_prestamo_interno', 'public') : null,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            // Egreso de la cuenta origen
            Movimiento::create([
                'cuenta_empresa_id'  => $request->cuenta_origen_id,
                'tipo'               => 'egreso',
                'categoria'          => 'prestamo_otorgado',
                'monto'              => $request->monto,
                'moneda'             => $request->moneda,
                'tipo_cambio'        => 1,
                'monto_bolivianos'   => $request->monto,
                'fecha'              => $request->fecha_prestamo,
                'concepto'           => 'Préstamo otorgado: ' . ($request->concepto ?? ''),
                'codigo_seguimiento' => $prestamo->codigo_seguimiento,
                'origen_type'        => PrestamoInterno::class,
                'origen_id'          => $prestamo->id,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            // Ingreso en la cuenta destino
            Movimiento::create([
                'cuenta_empresa_id'  => $request->cuenta_destino_id,
                'tipo'               => 'ingreso',
                'categoria'          => 'prestamo_recibido',
                'monto'              => $request->monto,
                'moneda'             => $request->moneda,
                'tipo_cambio'        => 1,
                'monto_bolivianos'   => $request->monto,
                'fecha'              => $request->fecha_prestamo,
                'concepto'           => 'Préstamo recibido: ' . ($request->concepto ?? ''),
                'codigo_seguimiento' => $prestamo->codigo_seguimiento,
                'origen_type'        => PrestamoInterno::class,
                'origen_id'          => $prestamo->id,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);
        });

        Alert::success('Guardado', 'Préstamo registrado y saldos actualizados.');
        return redirect()->route('prestamos_internos.index');
    }

    // Solo se puede editar mientras no tenga ninguna devolución registrada: una
    // vez que hay movimientos de devolución asociados, cambiar el monto original
    // dejaría el historial de devoluciones sin sentido (podría superar el monto).
    public function update(Request $request, string $uuid)
    {
        $prestamo = PrestamoInterno::where('uuid', $uuid)->firstOrFail();

        if ($prestamo->estado !== 'pendiente') {
            Alert::error('No editable', 'Este préstamo ya tiene devoluciones registradas y no se puede editar.');
            return redirect()->route('prestamos_internos.index');
        }

        $request->validate([
            'monto'              => 'required|numeric|min:0.01',
            'fecha_prestamo'     => 'required|date',
            'fecha_vencimiento'  => 'nullable|date|after:fecha_prestamo',
            'concepto'           => 'nullable|string',
            'metodo_pago'        => 'nullable|in:TRANSFERENCIA,QR',
            'codigo_seguimiento' => 'required_if:metodo_pago,TRANSFERENCIA|nullable|string|max:100',
            'voucher'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'codigo_seguimiento.required_if' => 'El código de la transferencia es obligatorio.',
            'voucher.mimes'                  => 'El comprobante debe ser JPG, PNG o PDF.',
            'voucher.max'                    => 'El comprobante no debe superar los 5 MB.',
        ]);

        DB::transaction(function () use ($request, $prestamo) {
            $codigoActualSiEraQr = $prestamo->metodo_pago === 'QR' ? $prestamo->codigo_seguimiento : null;

            $datos = [
                'monto_original'     => $request->monto,
                'fecha_prestamo'     => $request->fecha_prestamo,
                'fecha_vencimiento'  => $request->fecha_vencimiento,
                'concepto'           => $request->concepto,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $this->resolverCodigoSeguimiento($request->metodo_pago, $request->codigo_seguimiento, $codigoActualSiEraQr),
                'updated_by'         => auth()->id(),
            ];

            if ($request->hasFile('voucher')) {
                $anterior = $prestamo->voucher;
                $datos['voucher'] = $request->file('voucher')->store('vouchers_prestamo_interno', 'public');
                if ($anterior) {
                    Storage::disk('public')->delete($anterior);
                }
            }

            $prestamo->update($datos);

            // Los hooks de Movimiento revierten el efecto anterior en la cuenta
            // y aplican el nuevo automáticamente al detectar el cambio de monto/fecha.
            Movimiento::where('origen_type', PrestamoInterno::class)
                ->where('origen_id', $prestamo->id)
                ->get()
                ->each(function ($mov) use ($request, $prestamo) {
                    $mov->update([
                        'monto'              => $request->monto,
                        'fecha'              => $request->fecha_prestamo,
                        'concepto'           => ($mov->tipo === 'egreso' ? 'Préstamo otorgado: ' : 'Préstamo recibido: ') . ($request->concepto ?? ''),
                        'codigo_seguimiento' => $prestamo->codigo_seguimiento,
                    ]);
                });
        });

        Alert::success('Actualizado', 'Préstamo actualizado y saldos ajustados.');
        return redirect()->route('prestamos_internos.index');
    }

    // Solo se puede eliminar mientras no tenga ninguna devolución registrada,
    // por la misma razón que update(). Los movimientos se eliminan (soft delete)
    // y su hook deleted() revierte el efecto en los saldos automáticamente.
    public function destroy(string $uuid)
    {
        $prestamo = PrestamoInterno::where('uuid', $uuid)->firstOrFail();

        if ($prestamo->estado !== 'pendiente') {
            Alert::error('No se puede eliminar', 'Este préstamo ya tiene devoluciones registradas y no se puede eliminar.');
            return redirect()->route('prestamos_internos.index');
        }

        DB::transaction(function () use ($prestamo) {
            Movimiento::where('origen_type', PrestamoInterno::class)
                ->where('origen_id', $prestamo->id)
                ->get()
                ->each->delete();

            $prestamo->delete();
        });

        Alert::success('Eliminado', 'Préstamo eliminado y saldos revertidos.');
        return redirect()->route('prestamos_internos.index');
    }

    public function edit(string $uuid)
    {
        $prestamo = PrestamoInterno::where('uuid', $uuid)->firstOrFail();
        return response()->json($prestamo);
    }

    // Registrar devolución parcial o total
    public function devolver(Request $request, string $uuid)
    {
        $prestamo = PrestamoInterno::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'monto'              => 'required|numeric|min:0.01|max:' . $prestamo->monto_pendiente,
            'fecha'              => 'required|date',
            'metodo_pago'        => 'nullable|in:TRANSFERENCIA,QR',
            'codigo_seguimiento' => ['required_if:metodo_pago,TRANSFERENCIA', 'nullable', 'string', 'max:100', function ($attr, $value, $fail) {
                if (!$this->codigoDevolucionDisponible($value)) {
                    $fail('Ese código de transferencia ya está en uso por otro préstamo o devolución.');
                }
            }],
            'voucher' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'codigo_seguimiento.required_if' => 'El código de la transferencia es obligatorio.',
            'voucher.mimes' => 'El comprobante debe ser JPG, PNG o PDF.',
            'voucher.max'   => 'El comprobante no debe superar los 5 MB.',
        ]);

        DB::transaction(function () use ($request, $prestamo) {
            $prestamo->monto_devuelto += $request->monto;
            $prestamo->estado = $prestamo->monto_devuelto >= $prestamo->monto_original
                ? 'pagado'
                : 'pagado_parcial';
            $prestamo->updated_by = auth()->id();
            $prestamo->save();

            $devolucion = PrestamoInternoDevolucion::create([
                'prestamo_interno_id' => $prestamo->id,
                'monto'               => $request->monto,
                'fecha'               => $request->fecha,
                'concepto'            => $request->concepto,
                'metodo_pago'         => $request->metodo_pago,
                'codigo_seguimiento'  => $this->resolverCodigoSeguimientoDevolucion($request->metodo_pago, $request->codigo_seguimiento),
                'voucher'             => $request->hasFile('voucher') ? $request->file('voucher')->store('vouchers_devolucion_prestamo', 'public') : null,
                'created_by'          => auth()->id(),
            ]);

            // Egreso de cuenta destino (devuelve)
            Movimiento::create([
                'cuenta_empresa_id' => $prestamo->cuenta_destino_id,
                'tipo'              => 'egreso',
                'categoria'         => 'devolucion_prestamo',
                'monto'             => $request->monto,
                'moneda'            => $prestamo->moneda,
                'tipo_cambio'       => 1,
                'monto_bolivianos'  => $request->monto,
                'fecha'             => $request->fecha,
                'concepto'          => 'Devolución préstamo a ' . $prestamo->cuentaOrigen->nombre_cuenta,
                'codigo_seguimiento'=> $devolucion->codigo_seguimiento,
                'origen_type'       => PrestamoInternoDevolucion::class,
                'origen_id'         => $devolucion->id,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);

            // Ingreso en cuenta origen (recupera)
            Movimiento::create([
                'cuenta_empresa_id' => $prestamo->cuenta_origen_id,
                'tipo'              => 'ingreso',
                'categoria'         => 'devolucion_prestamo',
                'monto'             => $request->monto,
                'moneda'            => $prestamo->moneda,
                'tipo_cambio'       => 1,
                'monto_bolivianos'  => $request->monto,
                'fecha'             => $request->fecha,
                'concepto'          => 'Devolución recibida de ' . $prestamo->cuentaDestino->nombre_cuenta,
                'codigo_seguimiento'=> $devolucion->codigo_seguimiento,
                'origen_type'       => PrestamoInternoDevolucion::class,
                'origen_id'         => $devolucion->id,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);
        });

        Alert::success('Guardado', 'Devolución registrada correctamente.');
        return redirect()->route('prestamos_internos.index');
    }

    public function editDevolucion(string $uuid)
    {
        $devolucion = PrestamoInternoDevolucion::where('uuid', $uuid)->firstOrFail();
        return response()->json($devolucion);
    }

    // El monto no es editable aquí: cambiarlo alteraría monto_devuelto/estado del
    // préstamo y el efecto en los saldos de ambas cuentas (ver nota en destroyDevolucion).
    // Solo se ajustan fecha, concepto y voucher; los movimientos siguen a la fecha.
    public function updateDevolucion(Request $request, string $uuid)
    {
        $devolucion = PrestamoInternoDevolucion::where('uuid', $uuid)->firstOrFail();

        if ($devolucion->estado === 'cancelada') {
            Alert::error('No permitido', 'Esta devolución está cancelada y no se puede editar.');
            return redirect()->route('prestamos_internos.index');
        }

        $request->validate([
            'fecha'              => 'required|date',
            'concepto'           => 'nullable|string|max:255',
            'metodo_pago'        => 'nullable|in:TRANSFERENCIA,QR',
            'codigo_seguimiento' => ['required_if:metodo_pago,TRANSFERENCIA', 'nullable', 'string', 'max:100', function ($attr, $value, $fail) use ($devolucion) {
                if (!$this->codigoDevolucionDisponible($value) && $value !== $devolucion->codigo_seguimiento) {
                    $fail('Ese código de transferencia ya está en uso por otro préstamo o devolución.');
                }
            }],
            'voucher' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'codigo_seguimiento.required_if' => 'El código de la transferencia es obligatorio.',
            'voucher.mimes' => 'El comprobante debe ser JPG, PNG o PDF.',
            'voucher.max'   => 'El comprobante no debe superar los 5 MB.',
        ]);

        DB::transaction(function () use ($request, $devolucion) {
            $codigoActualSiEraQr = $devolucion->metodo_pago === 'QR' ? $devolucion->codigo_seguimiento : null;

            $datos = [
                'fecha'              => $request->fecha,
                'concepto'           => $request->concepto,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $request->metodo_pago === 'QR'
                    ? ($codigoActualSiEraQr ?: $this->resolverCodigoSeguimientoDevolucion('QR', null))
                    : ($request->codigo_seguimiento ?: null),
            ];

            if ($request->hasFile('voucher')) {
                $anterior = $devolucion->voucher;
                $datos['voucher'] = $request->file('voucher')->store('vouchers_devolucion_prestamo', 'public');
                if ($anterior) {
                    Storage::disk('public')->delete($anterior);
                }
            }

            $devolucion->update($datos);

            Movimiento::where('origen_type', PrestamoInternoDevolucion::class)
                ->where('origen_id', $devolucion->id)
                ->each(fn($m) => $m->update([
                    'fecha'              => $request->fecha,
                    'codigo_seguimiento' => $devolucion->codigo_seguimiento,
                ]));
        });

        Alert::success('Actualizado', 'Devolución actualizada correctamente.');
        return redirect()->route('prestamos_internos.index');
    }

    // No es eliminación física: la devolución queda marcada "cancelada" (igual
    // que un pago cancelado) y sus movimientos se anulan con soft delete, para
    // que sigan visibles tachados en Tesorería > Movimientos, como el resto del
    // sistema. El hook deleted() de Movimiento revierte el efecto en los saldos.
    public function destroyDevolucion(string $uuid)
    {
        $devolucion = PrestamoInternoDevolucion::where('uuid', $uuid)->firstOrFail();

        if ($devolucion->estado === 'cancelada') {
            Alert::error('No permitido', 'Esta devolución ya está cancelada.');
            return redirect()->route('prestamos_internos.index');
        }

        DB::transaction(function () use ($devolucion) {
            $prestamo = $devolucion->prestamo;

            Movimiento::where('origen_type', PrestamoInternoDevolucion::class)
                ->where('origen_id', $devolucion->id)
                ->get()
                ->each->delete();

            $prestamo->monto_devuelto -= $devolucion->monto;
            if ($prestamo->monto_devuelto < 0) {
                $prestamo->monto_devuelto = 0;
            }
            $prestamo->estado = $prestamo->monto_devuelto <= 0
                ? 'pendiente'
                : ($prestamo->monto_devuelto >= $prestamo->monto_original ? 'pagado' : 'pagado_parcial');
            $prestamo->updated_by = auth()->id();
            $prestamo->save();

            $devolucion->estado = 'cancelada';
            $devolucion->save();
        });

        Alert::success('Cancelada', 'Devolución cancelada y saldos revertidos.');
        return redirect()->route('prestamos_internos.index');
    }

    // QR: se genera un código interno único, el usuario no lo escribe.
    // Transferencia: se usa el código que el usuario ingresó (obligatorio, ya validado).
    private function resolverCodigoSeguimiento(?string $metodoPago, ?string $codigoIngresado, ?string $codigoActual = null): ?string
    {
        if ($metodoPago !== 'QR') {
            return $codigoIngresado ?: null;
        }

        if ($codigoActual) {
            return $codigoActual;
        }

        do {
            $codigo = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
        } while (
            PrestamoInterno::withTrashed()->where('codigo_seguimiento', $codigo)->exists()
            || PrestamoInternoDevolucion::withTrashed()->where('codigo_seguimiento', $codigo)->exists()
        );

        return $codigo;
    }

    // Mismo criterio que resolverCodigoSeguimiento(), aplicado a la devolución.
    private function resolverCodigoSeguimientoDevolucion(?string $metodoPago, ?string $codigoIngresado): ?string
    {
        if ($metodoPago !== 'QR') {
            return $codigoIngresado ?: null;
        }

        do {
            $codigo = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
        } while (!$this->codigoDevolucionDisponible($codigo));

        return $codigo;
    }

    private function codigoDevolucionDisponible(?string $codigo): bool
    {
        if (!$codigo) {
            return true;
        }

        return !PrestamoInterno::withTrashed()->where('codigo_seguimiento', $codigo)->exists()
            && !PrestamoInternoDevolucion::withTrashed()->where('codigo_seguimiento', $codigo)->exists();
    }
}
