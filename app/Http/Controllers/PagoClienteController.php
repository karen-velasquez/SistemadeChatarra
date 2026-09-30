<?php

namespace App\Http\Controllers;

use App\Models\Tramo;
use App\Models\PagoCliente;
use App\Models\LotePago;
use App\Models\CuentaBancaria;
use App\Models\CuentaEmpresa;
use App\Models\Empresa;
use App\Models\Movimiento;
use App\Models\Cliente;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Carbon\Carbon;

class PagoClienteController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;
    use \App\Http\Controllers\Concerns\GeneraCodigoSeguimientoUnico;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Tramos entregados con precio registrado
        $tramos = Tramo::with([
                'cliente',
                'contratoCamion.contrato.proveedor',
                'contratoCamion.camion.marca',
                'contratoCamion.camion.tipoVehiculo',
                'contratoCamion.camion.placaPais',
                'pagosCliente',
            ])
            ->where('estado', 'Entregado')
            ->whereNotNull('cliente_id')
            ->orderByDesc('fecha_llegada')
            ->get();

        $clientes = Cliente::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();

        // Proveedores presentes en las entregas listadas, para el filtro
        $proveedores = $tramos
            ->pluck('contratoCamion.contrato.proveedor')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        // Cuentas de empresa (tesorería) para cuenta destino
        $empresas = Empresa::with('cuentas')->whereNull('deleted_at')->get();

        // Datos simplificados para cobro masivo (evita closures complejas en @json Blade)
        $tramosMasivoData = $tramos
            ->filter(fn($t) => !is_null($t->precio_por_tonelada) && $t->saldo_cliente > 0)
            ->map(fn($t) => [
                'id'         => $t->id,
                'cliente_id' => $t->cliente_id,
                'contrato'   => $t->contratoCamion->contrato->numero_contrato ?? '—',
                'camion'     => $t->contratoCamion->camion->placa ?? '—',
                'fecha'      => $t->fecha_llegada?->format('d/m/Y') ?? '—',
                'moneda'     => $t->moneda_venta ?? 'BOB',
                'deuda'      => round($t->monto_deuda_cliente, 2),
                'cobrado'    => round($t->total_cobrado_cliente, 2),
                'saldo'      => round($t->saldo_cliente, 2),
            ])->values();

        $monedas = Parametro::where('tipo', 'tipo_moneda')->orderBy('valor')->get();
        $idempotencyToken = $this->generarToken('pago_cliente_store_token');
        $idempotencyTokenCobroMasivo = $this->generarToken('cobro_masivo_store_token');

        // Últimos cobros masivos, para el buscador de "Código de seguimiento" en el filtro
        $lotesCobroCliente = LotePago::where('tipo', 'cliente')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['uuid', 'codigo_provisional', 'codigo_real', 'created_at']);

        return view('pagos.clientes.index', compact('tramos', 'clientes', 'empresas', 'tramosMasivoData', 'monedas', 'idempotencyToken', 'idempotencyTokenCobroMasivo', 'proveedores', 'lotesCobroCliente'));
    }

    /**
     * Verificación AJAX mientras el usuario escribe el código de transferencia:
     * evita descubrir la colisión recién al enviar el formulario (lo cual cierra
     * el modal sin explicación, porque estos forms no reabren tras error 422).
     */
    public function verificarCodigo(Request $request)
    {
        $codigo = trim((string) $request->query('codigo'));
        if ($codigo === '') {
            return response()->json(['disponible' => true]);
        }

        $exceptoId = $request->query('except_id');
        $disponible = $this->codigoDisponible($codigo, $exceptoId ? (int) $exceptoId : null, PagoCliente::class);

        return response()->json(['disponible' => $disponible]);
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('pago_cliente_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('pagos.clientes.index');
        }
        $request->validate([
            'tramo_id'           => 'required|exists:tramos,id',
            'tipo_pago'          => 'required|in:adelanto,pago_final',
            'monto'              => 'required|numeric|min:0.01',
            'moneda_pago'        => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'tipo_cambio'        => 'required|numeric|min:0.0001',
            'fecha_pago'         => 'required|date',
            'metodo_pago'        => 'required|in:transferencia,qr',
            'codigo_seguimiento' => ['nullable', 'string', 'max:100', function ($attr, $value, $fail) {
                if (!$this->codigoDisponible($value)) {
                    $fail('Ese código de seguimiento ya está en uso por otro pago o lote. Verifique o ingrese uno distinto.');
                }
            }],
            'cuenta_origen_id'   => 'required|exists:cuentas_bancarias,id',
            'cuenta_destino_id'  => 'required|exists:cuentas_empresa,id',
            'observaciones'      => 'nullable|string|max:500',
            'voucher'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'tramo_id.required'         => 'Debe seleccionar la entrega.',
            'tipo_pago.required'        => 'Debe indicar el tipo de pago.',
            'monto.required'            => 'El monto es obligatorio.',
            'monto.min'                 => 'El monto debe ser mayor a cero.',
            'moneda_pago.required'      => 'Debe indicar la moneda.',
            'tipo_cambio.required'      => 'Debe indicar el tipo de cambio.',
            'fecha_pago.required'       => 'La fecha es obligatoria.',
            'metodo_pago.required'      => 'Debe indicar el método de pago.',
            'cuenta_origen_id.required' => 'La cuenta bancaria del cliente es obligatoria.',
            'cuenta_destino_id.required'=> 'La cuenta destino de la empresa es obligatoria.',
        ]);

        if ($request->metodo_pago === 'qr') {
            do {
                $codigo = 'QR-' . strtoupper(bin2hex(random_bytes(4)));
            } while (PagoCliente::withTrashed()->where('codigo_seguimiento', $codigo)->exists());
        } else {
            $codigo = $request->codigo_seguimiento ?: null;
        }

        DB::transaction(function () use ($request, $codigo) {
            $pago = PagoCliente::create([
                'tramo_id'           => $request->tramo_id,
                'tipo_pago'          => $request->tipo_pago,
                'monto'              => $request->monto,
                'moneda_pago'        => $request->moneda_pago,
                'tipo_cambio'        => $request->tipo_cambio,
                'fecha_pago'         => $request->fecha_pago,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $codigo,
                'cuenta_origen_id'   => $request->cuenta_origen_id ?: null,
                'cuenta_destino_id'  => $request->cuenta_destino_id ?: null,
                'observaciones'      => $request->observaciones ?: null,
                'voucher'            => $request->hasFile('voucher') ? $request->file('voucher')->store('vouchers_pago_cliente', 'public') : null,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            // Registrar movimiento en tesorería si se seleccionó cuenta destino
            if ($request->cuenta_destino_id) {
                $tramo = Tramo::find($request->tramo_id);
                $conceptoDetalle = ($tramo->cliente->nombre ?? 'Cliente');
                $conceptoDetalle .= ' - Tramo ' . ($tramo->origen ?? '') . ' → ' . ($tramo->destino ?? '');
                if ($tramo->peso_llegada) {
                    $conceptoDetalle .= ' (' . number_format($tramo->peso_llegada, 2) . ' t)';
                }

                Movimiento::registrarDePago($pago, 'ingreso', 'pago_cliente', $request->cuenta_destino_id, 'Cobro cliente: ' . $conceptoDetalle, $request->observaciones);
            }
        });

        Alert::success('Éxito', 'Pago del cliente registrado correctamente.');
        return redirect()->route('pagos.clientes.index');
    }

    public function setPrecio(Request $request, $id)
    {
        $tramo = Tramo::findOrFail($id);
        $request->validate([
            'precio_por_tonelada'     => 'required|numeric|min:0.0001',
            'moneda_venta'            => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'peso_llegada'            => 'required|numeric|min:0.001',
            'fecha_llegada'           => 'required|date',
            'empresa_facturadora_id'  => 'required|exists:empresas,id',
        ], [
            'precio_por_tonelada.required' => 'El precio por tonelada es obligatorio.',
            'precio_por_tonelada.min'      => 'El precio debe ser mayor a cero.',
            'moneda_venta.required'        => 'La moneda es obligatoria.',
            'peso_llegada.required'        => 'Las toneladas de llegada son obligatorias.',
            'peso_llegada.min'             => 'Las toneladas deben ser mayores a 0.',
            'fecha_llegada.required'       => 'La fecha de llegada es obligatoria.',
            'empresa_facturadora_id.required' => 'Debe seleccionar la empresa que facturará esta entrega.',
        ]);

        // Las toneladas solo se pueden corregir aquí mientras no haya ningún
        // cobro registrado (el botón que abre este modal ya lo garantiza:
        // solo aparece cuando $cobrado == 0).
        $tramo->update([
            'precio_por_tonelada'    => $request->precio_por_tonelada,
            'moneda_venta'           => $request->moneda_venta,
            'peso_llegada'           => $request->peso_llegada,
            'fecha_llegada'          => $request->fecha_llegada,
            'empresa_facturadora_id' => $request->empresa_facturadora_id,
            'updated_by'             => auth()->id(),
        ]);

        \App\Models\Empresa::actualizarPrecioReferencia(
            (int) $request->empresa_facturadora_id,
            (float) $request->precio_por_tonelada
        );

        Alert::success('Éxito', 'Precio por tonelada registrado correctamente.');
        return redirect()->route('pagos.clientes.index');
    }

    public function cobroMasivo(Request $request)
    {
        if (!$this->tokenValido('cobro_masivo_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este cobro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('pagos.clientes.index');
        }

        $request->validate([
            'cliente_id'        => 'required|exists:clientes,id',
            'tramo_ids'         => 'required|array|min:1',
            'tramo_ids.*'       => 'exists:tramos,id',
            'monto_total'       => 'required|numeric|min:0.01',
            'tipo_cambio'       => 'nullable|numeric|min:0.0001',
            'fecha_pago'        => 'required|date',
            'metodo_pago'       => 'required|in:transferencia,qr',
            'codigo_seguimiento'=> ['nullable', 'string', 'max:100', function ($attr, $value, $fail) {
                if (!$this->codigoDisponible($value)) {
                    $fail('Ese código de seguimiento ya está en uso por otro pago o lote. Verifique o ingrese uno distinto.');
                }
            }],
            'cuenta_origen_id'  => 'required|exists:cuentas_bancarias,id',
            'cuenta_destino_id' => 'required|exists:cuentas_empresa,id',
            'observaciones'     => 'nullable|string|max:500',
        ], [
            'tramo_ids.required'  => 'Debe seleccionar al menos una entrega.',
            'monto_total.required'=> 'El monto total es obligatorio.',
        ]);

        $cliente       = \App\Models\Cliente::findOrFail($request->cliente_id);
        $cuentaDestino = CuentaEmpresa::findOrFail($request->cuenta_destino_id);
        $monedaPago    = $cuentaDestino->moneda;
        $tipoCambio    = $monedaPago === 'BOB' ? 1 : (float) $request->tipo_cambio;

        // El QR lo genera el sistema: es un código provisional hasta que el banco lo
        // confirme. Si es transferencia, el código ya lo escribió el usuario a mano
        // (normalmente copiado del comprobante), así que ya es el código real.
        $esProvisional = $request->metodo_pago === 'qr';
        if ($esProvisional) {
            $codigo = $this->generarCodigoUnico('QR');
        } else {
            $codigo = $request->codigo_seguimiento ?: null;
        }

        $tramos        = Tramo::whereIn('id', $request->tramo_ids)
                            ->where('cliente_id', $request->cliente_id)
                            ->get();

        $lineas = DB::transaction(function () use ($request, $cliente, $monedaPago, $tipoCambio, $codigo, $esProvisional, $tramos) {
            // Agrupa todas las líneas de este cobro masivo, igual que ya se hace
            // para pago masivo a proveedores/camiones: permite editar el código
            // de transferencia real después desde Tesorería → Lotes de Pago.
            $lote = LotePago::create([
                'tipo'               => 'cliente',
                // Cobro a cliente no pasa por el flujo pendiente/confirmar de pago
                // masivo proveedor/camión: el movimiento se crea aquí mismo, así que
                // nace confirmado (el default de la columna es 'pendiente').
                'estado'             => 'confirmado',
                'codigo_provisional' => $esProvisional ? $codigo : null,
                'codigo_real'        => $esProvisional ? null : $codigo,
                'fecha_pago'         => $request->fecha_pago,
                'metodo_pago'        => $request->metodo_pago,
                'cuenta_origen_id'   => $request->cuenta_origen_id,
                'observaciones'      => $request->observaciones ?: null,
                'created_by'         => auth()->id(),
            ]);

            $montoRestante = round((float) $request->monto_total, 2);
            $lineas        = []; // resumen para mostrar al usuario
            $cuentaOrigenInfo = null;

            foreach ($tramos as $tramo) {
                if ($montoRestante <= 0) break;

                $saldo = $tramo->saldo_cliente;
                if ($saldo <= 0) continue;

                $montoPago     = min($saldo, $montoRestante);
                $montoRestante = round($montoRestante - $montoPago, 2);
                $esFinal       = round($montoPago, 2) >= round($saldo, 2);
                $contrato      = $tramo->contratoCamion->contrato->numero_contrato ?? '—';
                $camion        = $tramo->contratoCamion->camion->placa ?? '—';

                // Info de cuenta origen para el concepto del movimiento
                $cuentaOrigen     = $request->cuenta_origen_id
                    ? \App\Models\CuentaBancaria::with('banco')->find($request->cuenta_origen_id)
                    : null;
                $cuentaOrigenInfo = $cuentaOrigen
                    ? (($cuentaOrigen->banco->nombre ?? '') . ' ' . $cuentaOrigen->numero_cuenta)
                    : null;

                // Dejar constancia de que el cobro vino de un cobro masivo, se hayan
                // escrito observaciones o no. Si luego cambia el código real del lote,
                // LotePagoController::actualizarCodigo reemplaza este texto también.
                $notaMasivo    = 'Cobro masivo (código ' . $codigo . ').';
                $observaciones = trim($request->observaciones ?: '') !== ''
                    ? trim($request->observaciones) . ' — ' . $notaMasivo
                    : $notaMasivo;

                $pago = PagoCliente::create([
                    'tramo_id'           => $tramo->id,
                    'lote_pago_id'       => $lote->id,
                    'tipo_pago'          => $esFinal ? 'pago_final' : 'adelanto',
                    'monto'              => $montoPago,
                    'moneda_pago'        => $monedaPago,
                    'tipo_cambio'        => $tipoCambio,
                    'fecha_pago'         => $request->fecha_pago,
                    'metodo_pago'        => $request->metodo_pago,
                    'codigo_seguimiento' => $codigo,
                    'cuenta_origen_id'   => $request->cuenta_origen_id,
                    'cuenta_destino_id'  => $request->cuenta_destino_id,
                    'observaciones'      => $observaciones,
                    'created_by'         => auth()->id(),
                    'updated_by'         => auth()->id(),
                ]);

                $obs = 'Ref: ' . $codigo;
                if ($cuentaOrigenInfo) $obs .= ' | Desde: ' . $cuentaOrigenInfo . ' (' . $cliente->nombre . ')';
                if ($request->observaciones) $obs .= ' | ' . $request->observaciones;

                Movimiento::registrarDePago($pago, 'ingreso', 'pago_cliente', $request->cuenta_destino_id, 'Cobro cliente: ' . $cliente->nombre . ' — ' . $contrato . ' (' . $camion . ')', $obs);

                $lineas[] = [
                    'tipo'     => $esFinal ? 'Pago final' : 'Parcial',
                    'contrato' => $contrato,
                    'camion'   => $camion,
                    'monto'    => $monedaPago . ' ' . number_format($montoPago, 2),
                ];
            }

            // Excedente → anticipo
            if ($montoRestante > 0.009) {
                Movimiento::create([
                    'cuenta_empresa_id'  => $request->cuenta_destino_id,
                    'tipo'               => 'ingreso',
                    'categoria'          => 'anticipo_cliente',
                    'monto'              => $montoRestante,
                    'moneda'             => $monedaPago,
                    'tipo_cambio'        => $tipoCambio,
                    'monto_bolivianos'   => $montoRestante * $tipoCambio,
                    'fecha'              => $request->fecha_pago,
                    'concepto'           => 'Anticipo sin aplicar — ' . $cliente->nombre,
                    'codigo_seguimiento' => $codigo,
                    'observaciones'      => 'Ref: ' . $codigo
                        . ($cuentaOrigenInfo ? ' | Desde: ' . $cuentaOrigenInfo . ' (' . $cliente->nombre . ')' : '')
                        . ' | Excedente pendiente de justificar.',
                    'created_by'         => auth()->id(),
                    'updated_by'         => auth()->id(),
                ]);

                $lineas[] = [
                    'tipo'     => 'Anticipo (excedente)',
                    'contrato' => '—',
                    'camion'   => '—',
                    'monto'    => $monedaPago . ' ' . number_format($montoRestante, 2),
                ];
            }

            return $lineas;
        });

        // Pasar resumen a la vista para mostrarlo
        session()->flash('cobro_masivo_resumen', [
            'cliente' => $cliente->nombre,
            'codigo'  => $codigo,
            'metodo'  => $request->metodo_pago,
            'lineas'  => $lineas,
        ]);

        return redirect()->route('pagos.clientes.index');
    }

    public function destroy($uuid)
    {
        $pago = PagoCliente::where('uuid', $uuid)->firstOrFail();

        DB::transaction(function () use ($pago) {
            Movimiento::where('origen_type', PagoCliente::class)
                ->where('origen_id', $pago->id)
                ->each(fn($m) => $m->delete());

            $pago->delete();
        });

        Alert::success('Éxito', 'Cobro eliminado y movimiento en tesorería revertido.');
        return redirect()->route('pagos.clientes.index');
    }

    public function update(Request $request, $uuid)
    {
        $pago = PagoCliente::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'monto'      => 'required|numeric|min:0.01',
            'fecha_pago' => 'required|date',
        ]);

        $montoAnterior = $pago->monto;
        $diferencia    = round((float)$request->monto - $montoAnterior, 2);

        DB::transaction(function () use ($request, $pago, $montoAnterior, $diferencia) {
            $pago->update([
                'monto'      => $request->monto,
                'fecha_pago' => $request->fecha_pago,
                'updated_by' => auth()->id(),
            ]);

            // Ajuste en tesorería solo si el monto cambió
            if ($diferencia != 0) {
                $movOrig = Movimiento::where('origen_type', PagoCliente::class)
                    ->where('origen_id', $pago->id)
                    ->whereNull('deleted_at')
                    ->first();

                if ($movOrig) {
                    // Actualizar el movimiento original
                    $movOrig->update([
                        'monto'            => $request->monto,
                        'monto_bolivianos' => $request->monto * $pago->tipo_cambio,
                        'fecha'            => $request->fecha_pago,
                        'updated_by'       => auth()->id(),
                    ]);

                    // Registrar movimiento de ajuste para trazabilidad
                    $tipoAjuste = $diferencia > 0 ? 'ingreso' : 'egreso';
                    Movimiento::create([
                        'cuenta_empresa_id' => $movOrig->cuenta_empresa_id,
                        'tipo'              => $tipoAjuste,
                        'categoria'         => 'otro',
                        'monto'             => abs($diferencia),
                        'moneda'            => $pago->moneda_pago,
                        'tipo_cambio'       => $pago->tipo_cambio,
                        'monto_bolivianos'  => abs($diferencia) * $pago->tipo_cambio,
                        'fecha'             => now()->toDateString(),
                        'concepto'          => 'Ajuste cobro cliente — ' . ($pago->codigo_seguimiento ?? 'uuid:' . $pago->uuid),
                        'codigo_seguimiento'=> $pago->codigo_seguimiento,
                        'observaciones'     => 'Monto anterior: ' . $pago->moneda_pago . ' ' . number_format($montoAnterior, 2) . ' → nuevo: ' . $pago->moneda_pago . ' ' . number_format($request->monto, 2),
                        'origen_type'       => PagoCliente::class,
                        'origen_id'         => $pago->id,
                        'created_by'        => auth()->id(),
                        'updated_by'        => auth()->id(),
                    ]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    public function verVoucher($uuid)
    {
        $pago = PagoCliente::where('uuid', $uuid)->firstOrFail();

        abort_if(!$pago->voucher, 404, 'Este pago no tiene voucher adjunto.');

        $path = Storage::disk('public')->path($pago->voucher);

        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');

        return response()->file($path, ['Content-Type' => mime_content_type($path)]);
    }

    // API: detalle de una entrega (tramo) con sus pagos
    public function detalle($id)
    {
        $tramo = Tramo::with([
            'cliente',
            'contratoCamion.contrato',
            'contratoCamion.camion',
        ])->findOrFail($id);

        $pagos = PagoCliente::withTrashed()
            ->with(['cuentaOrigen.banco', 'cuentaDestino.empresa'])
            ->where('tramo_id', $tramo->id)
            ->orderBy('fecha_pago')
            ->get();

        return response()->json([
            'id'                  => $tramo->id,
            'cliente'             => $tramo->cliente->nombre ?? '—',
            'contrato'            => $tramo->contratoCamion->contrato->numero_contrato ?? '—',
            'camion'              => $tramo->contratoCamion->camion->placa ?? '—',
            'destino'             => $tramo->destino,
            'fecha_llegada'       => $tramo->fecha_llegada?->format('d/m/Y'),
            'peso_llegada'        => $tramo->peso_llegada,
            'precio_por_tonelada' => $tramo->precio_por_tonelada,
            'moneda_venta'        => $tramo->moneda_venta ?? 'BOB',
            'monto_deuda'         => $tramo->monto_deuda_cliente,
            'total_cobrado'       => $tramo->total_cobrado_cliente,
            'saldo'               => $tramo->saldo_cliente,
            'pagos'               => $pagos->map(fn($p) => [
                'uuid'           => $p->uuid,
                'tipo'           => $p->tipo_pago_label,
                'tipo_raw'       => $p->tipo_pago,
                'monto'          => $p->monto,
                'moneda_pago'    => $p->moneda_pago,
                'tipo_cambio'    => $p->tipo_cambio,
                'fecha'          => $p->fecha_pago->format('d/m/Y'),
                'fecha_raw'      => $p->fecha_pago->format('Y-m-d'),
                'metodo'         => $p->metodo_pago,
                'metodo_raw'     => $p->metodo_pago,
                'codigo'         => $p->codigo_seguimiento,
                'observaciones'  => $p->observaciones,
                'tiene_voucher'  => (bool) $p->voucher,
                'anulado'        => !is_null($p->deleted_at),
                'cuenta_origen'  => $p->cuentaOrigen ? [
                    'banco'  => $p->cuentaOrigen->banco->nombre ?? '—',
                    'numero' => $p->cuentaOrigen->numero_cuenta,
                    'moneda' => $p->cuentaOrigen->moneda,
                    'alias'  => $p->cuentaOrigen->alias,
                    'titular_cuenta' => $p->cuentaOrigen->nombre_titular_cuenta,
                    'tipo_relacion'  => $p->cuentaOrigen->tipo_relacion,
                ] : null,
                'cuenta_destino' => $p->cuentaDestino ? [
                    'titular' => $p->cuentaDestino->empresa->nombre ?? '—',
                    'alias'   => $p->cuentaDestino->nombre_cuenta,
                ] : null,
            ]),
        ]);
    }

    // API: cuentas bancarias del cliente para cuenta origen
    public function cuentasCliente(Request $request)
    {
        $clienteId = $request->cliente_id;
        if (!$clienteId) return response()->json([]);

        $cuentas = CuentaBancaria::with('banco')
            ->whereNull('deleted_at')
            ->where('titular_id', $clienteId)
            ->where('titular_type', 'App\Models\Cliente')
            ->get()
            ->map(function ($c) {
                $label = '';
                if ($c->nombre_titular_cuenta) {
                    $rel    = $c->tipo_relacion ? " ({$c->tipo_relacion})" : '';
                    $label .= "👤 {$c->nombre_titular_cuenta}{$rel} — ";
                }
                $label .= $c->banco->nombre . ' ' . $c->numero_cuenta;
                if ($c->alias) $label .= " ({$c->alias})";
                $label .= " [{$c->moneda}]";
                return ['id' => $c->id, 'label' => $label];
            });

        return response()->json($cuentas);
    }
}
