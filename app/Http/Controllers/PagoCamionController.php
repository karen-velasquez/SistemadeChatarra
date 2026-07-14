<?php

namespace App\Http\Controllers;

use App\Models\LotePago;
use App\Models\PagoCamion;
use App\Models\ContratoCamion;
use App\Models\CuentaBancaria;
use App\Models\CuentaEmpresa;
use App\Models\Empresa;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class PagoCamionController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $pagos = PagoCamion::with([
                'contratoCamion.contrato',
                'contratoCamion.camion.marca',
                'contratoCamion.camion.tipoVehiculo',
                'contratoCamion.camion.placaPais',
                'contratoCamion.conductor',
                'contratoCamion.camion.propietario',
                'cuentaOrigen.empresa',
                'cuentaDestino.banco',
                'receptor',
            ])
            ->orderByDesc('fecha_pago')
            ->orderByDesc('created_at')
            ->get();

        $idempotencyToken = $this->generarToken('pago_camion_store_token');
        return view('pagos.camiones.index', compact('pagos', 'idempotencyToken'));
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('pago_camion_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('pagos.camiones.index');
        }
        $request->validate([
            'contrato_camion_id' => 'required|exists:contrato_camiones,id',
            'tipo_pago'          => 'required|in:adelanto,flete,pago_final',
            'monto'              => 'required|numeric|min:0.01',
            'moneda_pago'        => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'tipo_cambio'        => 'required|numeric|min:0.0001',
            'fecha_pago'         => 'required|date',
            'receptor_type'      => 'nullable|in:conductor,propietario',
            'receptor_id'        => 'nullable|integer',
            'cuenta_origen_id'   => 'nullable|exists:cuentas_empresa,id',
            'cuenta_destino_id'  => 'nullable|exists:cuentas_bancarias,id',
            'metodo_pago'        => 'required|in:efectivo,transferencia,qr,cheque',
            'codigo_seguimiento' => 'nullable|string|max:100',
            'observaciones'      => 'nullable|string|max:500',
        ], [
            'contrato_camion_id.required' => 'Debe seleccionar la asignación.',
            'tipo_pago.required'          => 'Debe indicar el tipo de pago.',
            'monto.required'              => 'El monto es obligatorio.',
            'monto.min'                   => 'El monto debe ser mayor a cero.',
            'moneda_pago.required'        => 'Debe indicar la moneda del pago.',
            'tipo_cambio.required'        => 'Debe indicar el tipo de cambio.',
            'tipo_cambio.min'             => 'El tipo de cambio debe ser mayor a cero.',
            'fecha_pago.required'         => 'La fecha de pago es obligatoria.',
            'metodo_pago.required'        => 'Debe indicar el método de pago.',
        ]);

        $receptorType = null;
        if ($request->receptor_type === 'conductor') {
            $receptorType = 'App\Models\OperadorTransporte';
        } elseif ($request->receptor_type === 'propietario') {
            $receptorType = 'App\Models\OperadorTransporte';
        }

        $cc = ContratoCamion::with(['camion', 'conductor'])->findOrFail($request->contrato_camion_id);

        DB::transaction(function () use ($request, $receptorType, $cc) {
            $pago = PagoCamion::create([
                'contrato_camion_id' => $request->contrato_camion_id,
                'tipo_pago'          => $request->tipo_pago,
                'monto'              => $request->monto,
                'moneda_pago'        => $request->moneda_pago,
                'tipo_cambio'        => $request->tipo_cambio,
                'fecha_pago'         => $request->fecha_pago,
                'receptor_type'      => $receptorType,
                'receptor_id'        => $request->receptor_id ?: null,
                'cuenta_origen_id'   => $request->cuenta_origen_id ?: null,
                'cuenta_destino_id'  => $request->cuenta_destino_id ?: null,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $request->codigo_seguimiento ?: null,
                'observaciones'      => $request->observaciones ?: null,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            // Registrar egreso en tesorería si se seleccionó cuenta origen de empresa
            if ($request->cuenta_origen_id) {
                $conceptoDetalle = ($cc->camion->placa ?? 'Camión');
                if ($cc->contrato) {
                    $conceptoDetalle .= ' - Proveedor: ' . ($cc->contrato->proveedor->nombre ?? '');
                    if ($cc->contrato->numero_contrato) {
                        $conceptoDetalle .= ' (Contrato ' . $cc->contrato->numero_contrato . ')';
                    }
                }

                Movimiento::registrarDePago($pago, 'egreso', 'pago_camion', $request->cuenta_origen_id, 'Pago flete: ' . $conceptoDetalle, $request->observaciones);
            }
        });

        Alert::success('Éxito', 'Pago registrado correctamente.');
        return redirect()->route('seguimiento.index');
    }

    public function update(Request $request, $uuid)
    {
        $pago = PagoCamion::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'tipo_pago'          => 'required|in:adelanto,flete,pago_final',
            'monto'              => 'required|numeric|min:0.01',
            'moneda_pago'        => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'tipo_cambio'        => 'required|numeric|min:0.0001',
            'fecha_pago'         => 'required|date',
            'metodo_pago'        => 'required|in:efectivo,transferencia,qr,cheque',
            'codigo_seguimiento' => 'nullable|string|max:100',
        ]);

        $pago->update([
            'tipo_pago'          => $request->tipo_pago,
            'monto'              => $request->monto,
            'moneda_pago'        => $request->moneda_pago,
            'tipo_cambio'        => $request->tipo_cambio,
            'fecha_pago'         => $request->fecha_pago,
            'metodo_pago'        => $request->metodo_pago,
            'codigo_seguimiento' => $request->codigo_seguimiento ?: null,
            'updated_by'         => auth()->id(),
        ]);

        Alert::success('Éxito', 'Pago actualizado correctamente.');
        return redirect()->route('seguimiento.index');
    }

    public function destroy($uuid)
    {
        $pago = PagoCamion::where('uuid', $uuid)->firstOrFail();

        // Eliminar movimiento de tesorería asociado
        DB::transaction(function () use ($pago) {
            Movimiento::where('origen_type', PagoCamion::class)
                ->where('origen_id', $pago->id)
                ->each(fn($m) => $m->delete());

            $pago->delete();
        });

        Alert::success('Éxito', 'Pago eliminado y movimiento en tesorería revertido.');
        return redirect()->route('seguimiento.index');
    }

    // API: cuentas del receptor para poblar el select de cuenta destino
    public function cuentasReceptor(Request $request)
    {
        $id = $request->receptor_id;
        if (!$id) return response()->json([]);

        $cuentas = CuentaBancaria::with('banco')
            ->whereNull('deleted_at')
            ->where('titular_id', $id)
            ->where('titular_type', 'App\Models\OperadorTransporte')
            ->get()
            ->map(function ($c) {
                $titular = $c->nombre_titular_cuenta
                    ? $c->nombre_titular_cuenta
                    : null;
                $label = '';
                if ($titular) $label .= '👤 ' . $titular . ' — ';
                $label .= $c->banco->nombre . ' ' . $c->numero_cuenta;
                if ($c->alias) $label .= ' (' . $c->alias . ')';
                $label .= ' [' . $c->moneda . ']';
                return ['id' => $c->id, 'label' => $label];
            });

        return response()->json($cuentas);
    }

    // API: detalle financiero de una asignación para el modal
    public function detalle($id)
    {
        $cc = ContratoCamion::with([
            'contrato',
            'camion.marca',
            'camion.tipoVehiculo',
            'camion.placaPais',
            'conductor',
            'camion.propietario',
            'pagos.cuentaDestino.banco',
            'pagos.cuentaOrigen.empresa',
            'tramos',
        ])->findOrFail($id);

        return response()->json([
            'id'               => $cc->id,
            'camion'           => $cc->camion->placa . ' ' . $cc->camion->marca,
            'contrato'         => $cc->contrato->numero_contrato ?? '—',
            'conductor'        => $cc->conductor?->nombre_completo ?? '—',
            'propietario'      => $cc->camion->propietario?->nombre_completo ?? '—',
            'monto_acordado'   => $cc->monto_acordado,
            'descuento_monto'  => $cc->descuento_monto,
            'monto_neto'       => $cc->monto_neto,
            'total_pagado'     => $cc->total_pagado,
            'saldo_pendiente'  => $cc->saldo_pendiente,
            'moneda_flete'     => $cc->moneda_flete,
            'pagos'            => $cc->pagos->map(fn($p) => [
                'uuid'        => $p->uuid,
                'tipo'        => $p->tipo_pago_label,
                'tipo_raw'    => $p->tipo_pago,
                'monto'       => $p->monto,
                'moneda_pago' => $p->moneda_pago,
                'tipo_cambio' => $p->tipo_cambio,
                'monto_bob'   => $p->monto_en_bob,
                'fecha'       => $p->fecha_pago->format('d/m/Y'),
                'fecha_raw'   => $p->fecha_pago->format('Y-m-d'),
                'metodo'      => ucfirst($p->metodo_pago),
                'metodo_raw'  => $p->metodo_pago,
                'receptor'        => $p->nombre_receptor,
                'codigo'          => $p->codigo_seguimiento,
                'cuenta_destino'  => $p->cuentaDestino ? [
                    'banco'          => $p->cuentaDestino->banco->nombre ?? '—',
                    'numero'         => $p->cuentaDestino->numero_cuenta,
                    'moneda'         => $p->cuentaDestino->moneda,
                    'alias'          => $p->cuentaDestino->alias,
                    'titular_cuenta' => $p->cuentaDestino->nombre_titular_cuenta,
                    'tipo_relacion'  => $p->cuentaDestino->tipo_relacion,
                ] : null,
                'cuenta_origen'   => $p->cuentaOrigen ? [
                    'titular'        => $p->cuentaOrigen->empresa->nombre ?? '—',
                    'alias'          => $p->cuentaOrigen->alias,
                ] : null,
            ]),
        ]);
    }

    // Vista de pago masivo de camiones
    public function pagoMasivoView()
    {
        $contratosConSaldo = ContratoCamion::with([
                'contrato.proveedor',
                'camion',
                'conductor',
                'pagos',
                'tramos.cliente',
            ])
            ->whereHas('tramos', fn($q) => $q->where('estado', 'Entregado'))
            ->whereNotNull('monto_acordado')
            ->get()
            ->filter(fn($cc) => $cc->saldo_pendiente > 0)
            ->sortBy('fecha_asignacion');

        // Recolectar IDs únicos de operadores (conductor + propietario) para cargar sus cuentas
        $operadorIds = $contratosConSaldo->flatMap(function ($cc) {
            $ids = [];
            if ($cc->conductor_id) $ids[] = $cc->conductor_id;
            if ($cc->camion?->propietario_id) $ids[] = $cc->camion->propietario_id;
            return $ids;
        })->unique()->values();

        $cuentasPorOperador = CuentaBancaria::with('banco')
            ->whereNull('deleted_at')
            ->whereIn('titular_id', $operadorIds)
            ->where('titular_type', 'App\Models\OperadorTransporte')
            ->get()
            ->groupBy('titular_id');

        // Agrupar por proveedor y tomar máx 3 por proveedor
        $porProveedor = $contratosConSaldo
            ->groupBy(fn($cc) => $cc->contrato->proveedor_id ?? 0)
            ->map(fn($grupo) => [
                'proveedor' => $grupo->first()->contrato->proveedor,
                'contratos' => $grupo->take(3)->values(),
            ])
            ->filter(fn($g) => $g['proveedor'])
            ->values();

        $empresas = Empresa::with('cuentas')->whereNull('deleted_at')->get();

        return view('pagos.camiones.pago_masivo', compact('porProveedor', 'empresas', 'cuentasPorOperador'));
    }

    // Guardar pago masivo de camiones
    public function pagoMasivoStore(Request $request)
    {
        $request->validate([
            'contrato_camion_ids'  => 'required|array|min:1',
            'contrato_camion_ids.*'=> 'exists:contrato_camiones,id',
            'cuenta_origen_id'     => 'required|exists:cuentas_empresa,id',
            'fecha_pago'           => 'required|date',
            'metodo_pago'          => 'required|in:transferencia,qr',
            'codigo_seguimiento'   => 'nullable|string|max:100',
            'cuenta_destino'       => 'nullable|array',
            'cuenta_destino.*'     => 'nullable|exists:cuentas_bancarias,id',
            'observaciones'        => 'nullable|string|max:500',
        ], [
            'contrato_camion_ids.required' => 'Debe seleccionar al menos un camión.',
            'cuenta_origen_id.required'    => 'Debe seleccionar la cuenta desde donde se realiza el pago.',
        ]);

        $cuenta     = CuentaEmpresa::findOrFail($request->cuenta_origen_id);
        $monedaPago = $cuenta->moneda;
        $tipoCambio = 1;

        $contratosChk = ContratoCamion::with('pagos')
            ->whereIn('id', $request->contrato_camion_ids)
            ->get();
        $totalAPagar = $contratosChk->sum(fn($cc) => max(0, $cc->saldo_pendiente));

        if ($cuenta->saldo_actual < $totalAPagar) {
            return back()
                ->withInput()
                ->withErrors(['cuenta_origen_id' =>
                    'Saldo insuficiente. La cuenta "' . $cuenta->nombre_cuenta . '" tiene ' .
                    $cuenta->moneda . ' ' . number_format($cuenta->saldo_actual, 2) .
                    ' y el total a pagar es ' . $cuenta->moneda . ' ' . number_format($totalAPagar, 2) . '.'
                ]);
        }

        $prefijo    = $request->metodo_pago === 'qr' ? 'QR' : 'TRANS';
        $codigoLote = $prefijo . '-' . strtoupper(bin2hex(random_bytes(4)));

        $lineas = DB::transaction(function () use ($request, $monedaPago, $tipoCambio, $codigoLote) {
            $lote = LotePago::create([
                'tipo'               => 'camion',
                'codigo_provisional' => $codigoLote,
                'fecha_pago'         => $request->fecha_pago,
                'metodo_pago'        => $request->metodo_pago,
                'cuenta_origen_id'   => $request->cuenta_origen_id,
                'observaciones'      => $request->observaciones ?: null,
                'created_by'         => auth()->id(),
            ]);

            $contratos      = ContratoCamion::with(['contrato.proveedor', 'camion', 'pagos'])
                ->whereIn('id', $request->contrato_camion_ids)
                ->get();
            $cuentaDestino  = $request->input('cuenta_destino', []);

            $lineas = [];

            foreach ($contratos as $cc) {
                $saldo = $cc->saldo_pendiente;
                if ($saldo <= 0) continue;

                $proveedor  = $cc->contrato->proveedor->nombre ?? '—';
                $placa      = $cc->camion->placa ?? '—';
                $contrato   = $cc->contrato->numero_contrato ?? '—';
                $ctaDestId  = $cuentaDestino[$cc->id] ?? null;

                // Obtener cuenta destino, receptor y label para el resumen
                $ctaDestLabel  = '—';
                $receptorType  = null;
                $receptorId    = null;
                if ($ctaDestId) {
                    $ctaDest = CuentaBancaria::with('banco')->find($ctaDestId);
                    if ($ctaDest) {
                        $ctaDestLabel = ($ctaDest->nombre_titular_cuenta ? $ctaDest->nombre_titular_cuenta . ' — ' : '')
                            . ($ctaDest->banco->nombre ?? '') . ' ' . $ctaDest->numero_cuenta
                            . ($ctaDest->alias ? ' (' . $ctaDest->alias . ')' : '');

                        if ($ctaDest->titular_id && $ctaDest->titular_type) {
                            $receptorType = $ctaDest->titular_type;
                            $receptorId   = $ctaDest->titular_id;
                        }
                    }
                }

                $pago = PagoCamion::create([
                    'lote_pago_id'       => $lote->id,
                    'contrato_camion_id' => $cc->id,
                    'tipo_pago'          => 'pago_final',
                    'monto'              => $saldo,
                    'moneda_pago'        => $monedaPago,
                    'tipo_cambio'        => $tipoCambio,
                    'fecha_pago'         => $request->fecha_pago,
                    'metodo_pago'        => $request->metodo_pago,
                    'codigo_seguimiento' => $codigoLote,
                    'cuenta_origen_id'   => $request->cuenta_origen_id,
                    'cuenta_destino_id'  => $ctaDestId ?: null,
                    'receptor_type'      => $receptorType,
                    'receptor_id'        => $receptorId,
                    'observaciones'      => $request->observaciones ?: null,
                    'created_by'         => auth()->id(),
                    'updated_by'         => auth()->id(),
                ]);

                Movimiento::registrarDePago($pago, 'egreso', 'pago_camion', $request->cuenta_origen_id, 'Pago flete: ' . $placa . ' — ' . $proveedor . ' (' . $contrato . ')', $request->observaciones ?: null);

                $lineas[] = [
                    'proveedor'     => $proveedor,
                    'camion'        => $placa,
                    'contrato'      => $contrato,
                    'cuenta_destino'=> $ctaDestLabel,
                    'monto'         => $monedaPago . ' ' . number_format($saldo, 2),
                ];
            }

            return $lineas;
        });

        session()->flash('pago_masivo_camion_resumen', [
            'codigo' => $codigoLote,
            'metodo' => $request->metodo_pago,
            'lineas' => $lineas,
        ]);

        Alert::success('Éxito', 'Pago masivo de camiones registrado correctamente.');
        return redirect()->route('pagos.camiones.pago_masivo');
    }

}
