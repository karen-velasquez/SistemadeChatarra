<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\CuentaEmpresa;
use App\Models\LotePago;
use App\Models\PagoProveedor;
use App\Models\CuentaBancaria;
use App\Models\Empresa;
use App\Models\Movimiento;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PagoProveedorController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $contratos = Contrato::with(['proveedor', 'pagosProveedor', 'contratoCamiones.tramos'])
            ->whereNotNull('proveedor_id')
            ->whereNotNull('monto_total')
            ->orderByDesc('created_at')
            ->get();

        $proveedores = Proveedor::whereNull('deleted_at')->orderBy('nombre')->get();

        $empresas = Empresa::with('cuentas')->whereNull('deleted_at')->get();
        $idempotencyToken = $this->generarToken('pago_proveedor_store_token');

        return view('pagos.proveedores.index', compact('contratos', 'proveedores', 'empresas', 'idempotencyToken'));
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('pago_proveedor_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('pagos.proveedores.index');
        }
        $request->validate([
            'contrato_id'        => 'required|exists:contratos,id',
            'tipo_pago'          => 'required|in:adelanto,parcial,pago_final',
            'monto'              => 'required|numeric|min:0.01',
            'moneda_pago'        => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'tipo_cambio'        => 'required|numeric|min:0.0001',
            'fecha_pago'         => 'required|date',
            'metodo_pago'        => 'required|in:efectivo,transferencia,qr,cheque',
            'codigo_seguimiento' => 'nullable|string|max:100',
            'cuenta_origen_id'   => 'nullable|exists:cuentas_empresa,id',
            'cuenta_destino_id'  => 'nullable|exists:cuentas_bancarias,id',
            'observaciones'      => 'nullable|string|max:500',
        ], [
            'contrato_id.required' => 'Debe seleccionar el contrato.',
            'tipo_pago.required'   => 'Debe indicar el tipo de pago.',
            'monto.required'       => 'El monto es obligatorio.',
            'monto.min'            => 'El monto debe ser mayor a cero.',
            'moneda_pago.required' => 'Debe indicar la moneda del pago.',
            'tipo_cambio.required' => 'Debe indicar el tipo de cambio.',
            'tipo_cambio.min'      => 'El tipo de cambio debe ser mayor a cero.',
            'fecha_pago.required'  => 'La fecha de pago es obligatoria.',
            'metodo_pago.required' => 'Debe indicar el método de pago.',
        ]);

        $pago = PagoProveedor::create([
            'contrato_id'        => $request->contrato_id,
            'tipo_pago'          => $request->tipo_pago,
            'monto'              => $request->monto,
            'moneda_pago'        => $request->moneda_pago,
            'tipo_cambio'        => $request->tipo_cambio,
            'fecha_pago'         => $request->fecha_pago,
            'metodo_pago'        => $request->metodo_pago,
            'codigo_seguimiento' => $request->codigo_seguimiento ?: null,
            'cuenta_origen_id'   => $request->cuenta_origen_id ?: null,
            'cuenta_destino_id'  => $request->cuenta_destino_id ?: null,
            'observaciones'      => $request->observaciones ?: null,
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);

        // Registrar egreso en tesorería si se seleccionó cuenta origen de empresa
        if ($request->cuenta_origen_id) {
            $contrato = Contrato::find($request->contrato_id);
            $conceptoDetalle = ($contrato->proveedor->nombre ?? 'Proveedor');
            if ($contrato->numero_contrato) {
                $conceptoDetalle .= ' - Contrato ' . $contrato->numero_contrato;
            }

            Movimiento::create([
                'cuenta_empresa_id'  => $request->cuenta_origen_id,
                'tipo'               => 'egreso',
                'categoria'          => 'pago_proveedor',
                'monto'              => $request->monto,
                'moneda'             => $request->moneda_pago,
                'tipo_cambio'        => $request->tipo_cambio,
                'monto_bolivianos'   => $request->monto * $request->tipo_cambio,
                'fecha'              => $request->fecha_pago,
                'concepto'           => 'Pago proveedor: ' . $conceptoDetalle,
                'codigo_seguimiento' => $request->codigo_seguimiento ?: null,
                'observaciones'      => $request->observaciones,
                'origen_type'        => PagoProveedor::class,
                'origen_id'          => $pago->id,
                'created_by'         => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);
        }

        Alert::success('Éxito', 'Pago al proveedor registrado correctamente.');
        return redirect()->route('pagos.proveedores.index');
    }

    public function update(Request $request, $uuid)
    {
        $pago = PagoProveedor::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'tipo_pago'          => 'required|in:adelanto,pago_final',
            'monto'              => 'required|numeric|min:0.01',
            'moneda_pago'        => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'tipo_cambio'        => 'required|numeric|min:0.0001',
            'fecha_pago'         => 'required|date',
            'metodo_pago'        => 'required|in:transferencia,qr,cheque',
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
        return redirect()->route('pagos.proveedores.index');
    }

    public function destroy($uuid)
    {
        $pago = PagoProveedor::where('uuid', $uuid)->firstOrFail();

        Movimiento::where('origen_type', PagoProveedor::class)
            ->where('origen_id', $pago->id)
            ->each(fn($m) => $m->delete());

        $pago->delete();
        Alert::success('Éxito', 'Pago eliminado y movimiento en tesorería revertido.');
        return redirect()->route('pagos.proveedores.index');
    }

    // API: detalle financiero de un contrato para el modal
    public function detalle($id)
    {
        $contrato = Contrato::with([
            'proveedor',
            'pagosProveedor.cuentaDestino.banco',
            'pagosProveedor.cuentaOrigen.empresa',
        ])->findOrFail($id);

        return response()->json([
            'id'              => $contrato->id,
            'numero'          => $contrato->numero_contrato,
            'proveedor'       => $contrato->proveedor->nombre ?? '—',
            'monto_total'     => $contrato->monto_total,
            'moneda'          => $contrato->moneda ?? 'BOB',
            'total_pagado'    => $contrato->total_pagado_proveedor,
            'saldo_pendiente' => $contrato->saldo_pendiente_proveedor,
            'pagos'           => $contrato->pagosProveedor->map(fn($p) => [
                'uuid'           => $p->uuid,
                'tipo'           => $p->tipo_pago_label,
                'monto'          => $p->monto,
                'moneda_pago'    => $p->moneda_pago,
                'tipo_cambio'    => $p->tipo_cambio,
                'fecha'          => $p->fecha_pago->format('d/m/Y'),
                'fecha_raw'      => $p->fecha_pago->format('Y-m-d'),
                'metodo'         => ucfirst($p->metodo_pago),
                'metodo_raw'     => $p->metodo_pago,
                'tipo_raw'       => $p->tipo_pago,
                'monto_bob'      => $p->monto_en_moneda_contrato,
                'codigo'         => $p->codigo_seguimiento,
                'cuenta_destino' => $p->cuentaDestino ? [
                    'banco'          => $p->cuentaDestino->banco->nombre ?? '—',
                    'numero'         => $p->cuentaDestino->numero_cuenta,
                    'moneda'         => $p->cuentaDestino->moneda,
                    'alias'          => $p->cuentaDestino->alias,
                    'titular_cuenta' => $p->cuentaDestino->nombre_titular_cuenta,
                    'tipo_relacion'  => $p->cuentaDestino->tipo_relacion,
                ] : null,
                'cuenta_origen'  => $p->cuentaOrigen ? [
                    'titular' => $p->cuentaOrigen->titular?->nombre_completo ?? '—',
                    'alias'   => $p->cuentaOrigen->alias,
                ] : null,
            ]),
        ]);
    }

    public function pagoMasivoView()
    {
        $contratos = Contrato::with([
                'proveedor',
                'pagosProveedor',
                'contratoCamiones.camion',
                'contratoCamiones.conductor',
                'contratoCamiones.tramos.pagosCliente',
                'contratoCamiones.tramos.cliente',
                'contratoCamiones.tramos.tramoPadre',
                'contratoCamiones.tramos.camion.marca',
                'contratoCamiones.tramos.conductor',
                'contratoCamiones.tramos.tramosHijos.tramosHijos.tramosHijos.tramosHijos',
                'contratoCamiones.tramos.tramosHijos.camion.marca',
                'contratoCamiones.tramos.tramosHijos.conductor',
                'contratoCamiones.tramos.tramosHijos.cliente',
                'contratoCamiones.tramos.tramosHijos.pagosCliente',
                'contratoCamiones.tramos.tramosHijos.contratoCamion.pagos',
                'contratoCamiones.tramos.contratoCamion.pagos',
                'contratoCamiones.pagos',
            ])
            ->whereNotNull('proveedor_id')
            ->whereNotNull('monto_total')
            ->whereRaw('monto_total > 0')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn($c) => $c->saldo_pendiente_proveedor > 0);

        $proveedorIds = $contratos->pluck('proveedor_id')->unique()->filter();

        $cuentasPorProveedor = CuentaBancaria::with('banco')
            ->whereNull('deleted_at')
            ->where('titular_type', 'App\Models\Proveedor')
            ->whereIn('titular_id', $proveedorIds)
            ->get()
            ->groupBy('titular_id');

        $empresas = Empresa::with('cuentas')->whereNull('deleted_at')->get();

        return view('pagos.proveedores.pago_masivo', compact('contratos', 'cuentasPorProveedor', 'empresas'));
    }

    public function pagoMasivoStore(Request $request)
    {
        $request->validate([
            'cuenta_origen_id' => 'required|exists:cuentas_empresa,id',
            'fecha_pago'       => 'required|date',
            'metodo_pago'      => 'required|in:transferencia,qr',
            'contrato_ids'     => 'required|array|min:1',
            'contrato_ids.*'   => 'exists:contratos,id',
            'porcentaje'       => 'required|array',
            'porcentaje.*'     => 'numeric|min:0.01|max:100',
            'cuenta_destino'   => 'required|array',
            'cuenta_destino.*' => 'exists:cuentas_bancarias,id',
        ]);

        $cuentaOrigen = CuentaEmpresa::with('empresa')->findOrFail($request->cuenta_origen_id);
        $monedaPago   = $cuentaOrigen->moneda ?? 'BOB';
        $tipoCambio   = 1;

        // Calcular total a pagar y verificar saldo suficiente
        $totalAPagar = 0;
        foreach ($request->contrato_ids as $cid) {
            $pct = (float) ($request->porcentaje[$cid] ?? 0);
            if ($pct <= 0) continue;
            $c = Contrato::find($cid);
            if (!$c) continue;
            $totalAPagar += round($c->saldo_pendiente_proveedor * $pct / 100, 2);
        }
        if ($cuentaOrigen->saldo_actual < $totalAPagar) {
            return back()->withInput()->withErrors([
                'cuenta_origen_id' => "Saldo insuficiente. Disponible: {$monedaPago} " . number_format($cuentaOrigen->saldo_actual, 2) . ", requerido: {$monedaPago} " . number_format($totalAPagar, 2) . '.',
            ]);
        }

        // Código provisional del lote (compartido por todos los pagos de este lote)
        $prefijo    = $request->metodo_pago === 'qr' ? 'QR' : 'TRANS';
        $codigoLote = $prefijo . '-' . strtoupper(\Illuminate\Support\Str::random(8));

        $lote = LotePago::create([
            'tipo'               => 'proveedor',
            'codigo_provisional' => $codigoLote,
            'fecha_pago'         => $request->fecha_pago,
            'metodo_pago'        => $request->metodo_pago,
            'cuenta_origen_id'   => $request->cuenta_origen_id,
            'observaciones'      => $request->observaciones ?: null,
            'created_by'         => auth()->id(),
        ]);

        $resumen = [];

        foreach ($request->contrato_ids as $contratoId) {
            $pct       = (float) ($request->porcentaje[$contratoId] ?? 0);
            $ctaDestId = $request->cuenta_destino[$contratoId] ?? null;

            if ($pct <= 0 || !$ctaDestId) continue;

            $contrato = Contrato::with('proveedor')->find($contratoId);
            if (!$contrato) continue;

            $saldo = $contrato->saldo_pendiente_proveedor;
            $monto = round($saldo * $pct / 100, 2);
            if ($monto <= 0) continue;

            $ctaDest = CuentaBancaria::with('banco')->find($ctaDestId);
            if (!$ctaDest) continue;

            $pago = PagoProveedor::create([
                'lote_pago_id'       => $lote->id,
                'contrato_id'        => $contratoId,
                'tipo_pago'          => 'parcial',
                'monto'              => $monto,
                'moneda_pago'        => $monedaPago,
                'tipo_cambio'        => $tipoCambio,
                'fecha_pago'         => $request->fecha_pago,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $codigoLote,
                'cuenta_origen_id'   => $request->cuenta_origen_id,
                'cuenta_destino_id'  => $ctaDest->id,
                'observaciones'      => $request->observaciones ?: null,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            $conceptoDetalle = ($contrato->proveedor->nombre ?? 'Proveedor');
            if ($contrato->numero_contrato) {
                $conceptoDetalle .= ' - Contrato ' . $contrato->numero_contrato;
            }

            Movimiento::create([
                'lote_pago_id'       => $lote->id,
                'cuenta_empresa_id'  => $request->cuenta_origen_id,
                'tipo'               => 'egreso',
                'categoria'          => 'pago_proveedor',
                'monto'              => $monto,
                'moneda'             => $monedaPago,
                'tipo_cambio'        => $tipoCambio,
                'monto_bolivianos'   => $monto,
                'fecha'              => $request->fecha_pago,
                'concepto'           => 'Pago masivo proveedor: ' . $conceptoDetalle,
                'codigo_seguimiento' => $codigoLote,
                'observaciones'      => $request->observaciones,
                'origen_type'        => PagoProveedor::class,
                'origen_id'          => $pago->id,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            $resumen[] = [
                'proveedor'      => $contrato->proveedor->nombre ?? '—',
                'contrato'       => $contrato->numero_contrato ?? '#' . $contrato->id,
                'porcentaje'     => $pct,
                'monto'          => $monto,
                'moneda'         => $monedaPago,
                'cuenta_destino' => ($ctaDest->banco->nombre ?? '') . ' ' . $ctaDest->numero_cuenta,
            ];
        }

        if (empty($resumen)) {
            Alert::warning('Sin pagos', 'No se registró ningún pago. Verifique los porcentajes y cuentas seleccionadas.');
            return back()->withInput();
        }

        $total = collect($resumen)->sum('monto');
        $lineas = collect($resumen)->map(fn($r) =>
            "{$r['proveedor']} ({$r['contrato']}): {$r['moneda']} " . number_format($r['monto'], 2) . " ({$r['porcentaje']}%) → {$r['cuenta_destino']}"
        )->implode(' | ');

        Alert::success('Pagos registrados', count($resumen) . ' pago(s) por ' . $monedaPago . ' ' . number_format($total, 2) . '. ' . $lineas);
        return redirect()->route('pagos.proveedores.index');
    }

    // API: cuentas bancarias del proveedor para cuenta destino
    public function cuentasProveedor(Request $request)
    {
        $proveedorId = $request->proveedor_id;
        if (!$proveedorId) return response()->json([]);

        $cuentas = CuentaBancaria::with('banco')
            ->whereNull('deleted_at')
            ->where('titular_id', $proveedorId)
            ->where('titular_type', 'App\Models\Proveedor')
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
