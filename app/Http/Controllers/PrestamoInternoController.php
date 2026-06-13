<?php

namespace App\Http\Controllers;

use App\Models\PrestamoInterno;
use App\Models\CuentaEmpresa;
use App\Models\Empresa;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class PrestamoInternoController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $prestamos = PrestamoInterno::with(['cuentaOrigen.empresa', 'cuentaDestino.empresa'])
            ->orderByDesc('fecha_prestamo')
            ->paginate(20);

        $empresas = Empresa::with('cuentas')->get();
        $idempotencyToken = $this->generarToken('prestamo_store_token');

        return view('prestamos_internos.index', compact('prestamos', 'empresas', 'idempotencyToken'));
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('prestamo_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('prestamos_internos.index');
        }
        $request->validate([
            'cuenta_origen_id'  => 'required|exists:cuentas_empresa,id',
            'cuenta_destino_id' => 'required|exists:cuentas_empresa,id|different:cuenta_origen_id',
            'monto'             => 'required|numeric|min:0.01',
            'moneda'            => 'required|string|max:10',
            'fecha_prestamo'    => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after:fecha_prestamo',
            'concepto'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $prestamo = PrestamoInterno::create([
                'cuenta_origen_id'  => $request->cuenta_origen_id,
                'cuenta_destino_id' => $request->cuenta_destino_id,
                'monto_original'    => $request->monto,
                'monto_devuelto'    => 0,
                'moneda'            => $request->moneda,
                'fecha_prestamo'    => $request->fecha_prestamo,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'estado'            => 'pendiente',
                'concepto'          => $request->concepto,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);

            // Egreso de la cuenta origen
            Movimiento::create([
                'cuenta_empresa_id' => $request->cuenta_origen_id,
                'tipo'              => 'egreso',
                'categoria'         => 'prestamo_otorgado',
                'monto'             => $request->monto,
                'moneda'            => $request->moneda,
                'tipo_cambio'       => 1,
                'monto_bolivianos'  => $request->monto,
                'fecha'             => $request->fecha_prestamo,
                'concepto'          => 'Préstamo otorgado: ' . ($request->concepto ?? ''),
                'origen_type'       => PrestamoInterno::class,
                'origen_id'         => $prestamo->id,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);

            // Ingreso en la cuenta destino
            Movimiento::create([
                'cuenta_empresa_id' => $request->cuenta_destino_id,
                'tipo'              => 'ingreso',
                'categoria'         => 'prestamo_recibido',
                'monto'             => $request->monto,
                'moneda'            => $request->moneda,
                'tipo_cambio'       => 1,
                'monto_bolivianos'  => $request->monto,
                'fecha'             => $request->fecha_prestamo,
                'concepto'          => 'Préstamo recibido: ' . ($request->concepto ?? ''),
                'origen_type'       => PrestamoInterno::class,
                'origen_id'         => $prestamo->id,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);
        });

        Alert::success('Guardado', 'Préstamo registrado y saldos actualizados.');
        return redirect()->route('prestamos_internos.index');
    }

    // Registrar devolución parcial o total
    public function devolver(Request $request, string $uuid)
    {
        $prestamo = PrestamoInterno::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . $prestamo->monto_pendiente,
            'fecha' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $prestamo) {
            $prestamo->monto_devuelto += $request->monto;
            $prestamo->estado = $prestamo->monto_devuelto >= $prestamo->monto_original
                ? 'pagado'
                : 'pagado_parcial';
            $prestamo->updated_by = auth()->id();
            $prestamo->save();

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
                'origen_type'       => PrestamoInterno::class,
                'origen_id'         => $prestamo->id,
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
                'origen_type'       => PrestamoInterno::class,
                'origen_id'         => $prestamo->id,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);
        });

        Alert::success('Guardado', 'Devolución registrada correctamente.');
        return redirect()->route('prestamos_internos.index');
    }
}
