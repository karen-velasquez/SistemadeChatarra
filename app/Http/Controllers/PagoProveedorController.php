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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class PagoProveedorController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;
    use \App\Http\Controllers\Concerns\GeneraCodigoSeguimientoUnico;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $contratos = Contrato::with(['proveedor', 'pagosProveedor', 'contratoCamiones.tramos', 'usuarioCreador'])
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
            'cuenta_origen_id'   => 'required|exists:cuentas_empresa,id',
            'cuenta_destino_id'  => 'required|exists:cuentas_bancarias,id',
            'observaciones'      => 'nullable|string|max:500',
            'voucher'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'contrato_id.required'       => 'Debe seleccionar el contrato.',
            'tipo_pago.required'         => 'Debe indicar el tipo de pago.',
            'monto.required'             => 'El monto es obligatorio.',
            'monto.min'                  => 'El monto debe ser mayor a cero.',
            'moneda_pago.required'       => 'Debe indicar la moneda del pago.',
            'tipo_cambio.required'       => 'Debe indicar el tipo de cambio.',
            'tipo_cambio.min'            => 'El tipo de cambio debe ser mayor a cero.',
            'fecha_pago.required'        => 'La fecha de pago es obligatoria.',
            'cuenta_origen_id.required'  => 'Debe seleccionar la cuenta de origen.',
            'cuenta_destino_id.required' => 'Debe seleccionar la cuenta destino.',
            'metodo_pago.required' => 'Debe indicar el método de pago.',
        ]);

        // En QR no se captura código: se genera uno para poder rastrear el pago.
        $codigoSeguimiento = $request->codigo_seguimiento ?: null;
        if (!$codigoSeguimiento && $request->metodo_pago === 'qr') {
            $codigoSeguimiento = $this->generarCodigoUnico('QR');
        }

        DB::transaction(function () use ($request, $codigoSeguimiento) {
            $pago = PagoProveedor::create([
                'contrato_id'        => $request->contrato_id,
                'tipo_pago'          => $request->tipo_pago,
                'monto'              => $request->monto,
                'moneda_pago'        => $request->moneda_pago,
                'tipo_cambio'        => $request->tipo_cambio,
                'fecha_pago'         => $request->fecha_pago,
                'metodo_pago'        => $request->metodo_pago,
                'codigo_seguimiento' => $codigoSeguimiento,
                'cuenta_origen_id'   => $request->cuenta_origen_id ?: null,
                'cuenta_destino_id'  => $request->cuenta_destino_id ?: null,
                'observaciones'      => $request->observaciones ?: null,
                'voucher'            => $request->hasFile('voucher') ? $request->file('voucher')->store('vouchers_pago_proveedor', 'public') : null,
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

                Movimiento::registrarDePago($pago, 'egreso', 'pago_proveedor', $request->cuenta_origen_id, 'Pago proveedor: ' . $conceptoDetalle, $request->observaciones);
            }
        });

        Alert::success('Éxito', 'Pago al proveedor registrado correctamente.');
        return redirect()->route('pagos.proveedores.index');
    }

    public function update(Request $request, $uuid)
    {
        $pago = PagoProveedor::where('uuid', $uuid)->firstOrFail();

        // Editable: el monto y, opcionalmente, el voucher. Moneda, tipo de cambio,
        // fecha y método se conservan tal como se registró el pago.
        $request->validate([
            'monto'   => 'required|numeric|min:0.01',
            'voucher' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'monto.required' => 'El monto es obligatorio.',
            'monto.min'      => 'El monto debe ser mayor a cero.',
            'voucher.mimes'  => 'El comprobante debe ser JPG, PNG o PDF.',
            'voucher.max'    => 'El comprobante no debe superar los 5 MB.',
        ]);

        DB::transaction(function () use ($request, $pago) {
            $datos = [
                'monto'      => $request->monto,
                'updated_by' => auth()->id(),
            ];

            // Voucher nuevo: reemplaza al anterior y borra el archivo viejo
            if ($request->hasFile('voucher')) {
                $anterior = $pago->voucher;
                $datos['voucher'] = $request->file('voucher')->store('vouchers_pago_proveedor', 'public');
                if ($anterior) {
                    Storage::disk('public')->delete($anterior);
                }
            }

            $pago->update($datos);

            // El movimiento de tesorería debe reflejar el monto editado.
            // El hook updated() de Movimiento recalcula monto_bolivianos y ajusta el saldo.
            Movimiento::where('origen_type', PagoProveedor::class)
                ->where('origen_id', $pago->id)
                ->each(fn($m) => $m->update([
                    'monto'      => $pago->monto,
                    'updated_by' => auth()->id(),
                ]));
        });

        if ($request->expectsJson()) {
            $contrato = $pago->contrato()->first();

            return response()->json([
                'ok'              => true,
                'message'         => 'Pago actualizado y movimiento en tesorería sincronizado.',
                'tiene_voucher'   => (bool) $pago->voucher,
                'contrato_id'     => $pago->contrato_id,
                'total_pagado'    => $contrato?->total_pagado_proveedor,
                'saldo_pendiente' => $contrato?->saldo_pendiente_proveedor,
            ]);
        }

        Alert::success('Éxito', 'Pago actualizado y movimiento en tesorería sincronizado.');
        return redirect()->route('pagos.proveedores.index');
    }

    public function destroy($uuid)
    {
        $pago = PagoProveedor::where('uuid', $uuid)->firstOrFail();

        DB::transaction(function () use ($pago) {
            Movimiento::where('origen_type', PagoProveedor::class)
                ->where('origen_id', $pago->id)
                ->each(fn($m) => $m->delete());

            $pago->delete();
        });

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
                'observaciones'  => $p->observaciones,
                'tiene_voucher'  => (bool) $p->voucher,
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

    public function verVoucher($uuid)
    {
        $pago = PagoProveedor::where('uuid', $uuid)->firstOrFail();

        abort_if(!$pago->voucher, 404, 'Este pago no tiene voucher adjunto.');

        $path = Storage::disk('public')->path($pago->voucher);

        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');

        return response()->file($path, ['Content-Type' => mime_content_type($path)]);
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
            'porcentaje.*'     => 'nullable|numeric|min:0|max:100',
            'cuenta_destino'   => 'required|array',
            'cuenta_destino.*' => 'nullable|exists:cuentas_bancarias,id',
            'vouchers'         => 'nullable|array',
            'vouchers.*'       => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            // Un contrato puede llegar como varios pagos reales al mismo monto/cuenta
            // (ej. dos transferencias de 400 y 600 en vez de una de 1000).
            'splits'           => 'nullable|array',
            'splits.*'         => 'array',
            'splits.*.*'       => 'numeric|min:0.01',
            'split_vouchers'   => 'nullable|array',
            'split_vouchers.*.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
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
        // ponytail: bloqueo de saldo insuficiente comentado a pedido del cliente,
        // mientras se ponen al día con los pagos de este mes. Restaurar cuando corresponda.
        // if ($cuentaOrigen->saldo_actual < $totalAPagar) {
        //     return back()->withInput()->withErrors([
        //         'cuenta_origen_id' => "Saldo insuficiente. Disponible: {$monedaPago} " . number_format($cuentaOrigen->saldo_actual, 2) . ", requerido: {$monedaPago} " . number_format($totalAPagar, 2) . '.',
        //     ]);
        // }

        // Código provisional del lote (compartido por todos los pagos de este lote)
        $prefijo    = $request->metodo_pago === 'qr' ? 'QR' : 'TRANS';
        $codigoLote = $this->generarCodigoUnico($prefijo);

        $resumen = DB::transaction(function () use ($request, $monedaPago, $tipoCambio, $codigoLote) {
            $lote = LotePago::create([
                'tipo'               => 'proveedor',
                'estado'             => 'pendiente',
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

                // Dejar constancia de que el pago vino de un lote masivo, se hayan
                // escrito observaciones o no.
                $notaMasivo    = 'Pago realizado mediante pago masivo (lote ' . $codigoLote . ').';
                $observaciones = trim($request->observaciones ?: '') !== ''
                    ? trim($request->observaciones) . ' — ' . $notaMasivo
                    : $notaMasivo;

                $conceptoDetalle = ($contrato->proveedor->nombre ?? 'Proveedor');
                if ($contrato->numero_contrato) {
                    $conceptoDetalle .= ' - Contrato ' . $contrato->numero_contrato;
                }

                // Un contrato puede llegar como varios pagos reales (mismo monto total,
                // misma cuenta) en vez de una sola transferencia: se crea un PagoProveedor
                // por cada sub-monto, cada uno con su propio voucher.
                $splitsContrato = $request->input("splits.$contratoId");
                $montosAPagar = $splitsContrato
                    ? array_values(array_filter($splitsContrato, fn($m) => (float) $m > 0))
                    : [$monto];

                foreach ($montosAPagar as $idxSplit => $montoPago) {
                    $montoPago = round((float) $montoPago, 2);
                    // Si el pago cubre el saldo pendiente liquida el contrato; si no, es un adelanto.
                    // Con splits, solo el último puede llegar a liquidar (se evalúa contra el saldo real).
                    $tipoPago = $montoPago >= round($saldo, 2) ? 'pago_final' : 'adelanto';

                    $voucherFile = $splitsContrato
                        ? $request->file("split_vouchers.$contratoId.$idxSplit")
                        : $request->file("vouchers.$contratoId");
                    $voucherPath = $voucherFile ? $voucherFile->store('vouchers_pago_proveedor', 'public') : null;

                    $pago = PagoProveedor::create([
                        'lote_pago_id'       => $lote->id,
                        'contrato_id'        => $contratoId,
                        'tipo_pago'          => $tipoPago,
                        'monto'              => $montoPago,
                        'moneda_pago'        => $monedaPago,
                        'tipo_cambio'        => $tipoCambio,
                        'fecha_pago'         => $request->fecha_pago,
                        'metodo_pago'        => $request->metodo_pago,
                        'codigo_seguimiento' => $codigoLote,
                        'cuenta_origen_id'   => $request->cuenta_origen_id,
                        'cuenta_destino_id'  => $ctaDest->id,
                        'voucher'            => $voucherPath,
                        'observaciones'      => $observaciones,
                        'created_by'         => auth()->id(),
                        'updated_by'         => auth()->id(),
                    ]);

                    // El movimiento de tesorería (y el descuento del saldo de la cuenta) se
                    // crea al confirmar el lote, no aquí: hasta entonces el pago es solo una
                    // orden generada, no una transferencia que el banco ya haya ejecutado.

                    $resumen[] = [
                        'proveedor'      => $contrato->proveedor->nombre ?? '—',
                        'contrato'       => $contrato->numero_contrato ?? '#' . $contrato->id,
                        'porcentaje'     => $pct,
                        'monto'          => $montoPago,
                        'moneda'         => $monedaPago,
                        'cuenta_destino' => ($ctaDest->banco->nombre ?? '') . ' ' . $ctaDest->numero_cuenta,
                    ];
                }
            }

            return $resumen;
        });

        if (empty($resumen)) {
            Alert::warning('Sin pagos', 'No se registró ningún pago. Verifique los porcentajes y cuentas seleccionadas.');
            return back()->withInput();
        }

        $total = collect($resumen)->sum('monto');

        // Resumen como tabla: en una sola línea separada por "|" era ilegible
        $filas = collect($resumen)->map(function ($r) {
            $prov = e($r['proveedor']);
            $ctr  = e($r['contrato']);
            $cta  = e($r['cuenta_destino']);
            $mto  = $r['moneda'] . ' ' . number_format($r['monto'], 2, ',', '.');
            $pct  = rtrim(rtrim(number_format($r['porcentaje'], 2, ',', '.'), '0'), ',');

            return "<tr>
                <td style='padding:6px 8px;border-bottom:1px solid #eee'>
                    <strong>{$prov}</strong><br>
                    <span style='color:#6c757d;font-size:.85em'>{$ctr}</span>
                </td>
                <td style='padding:6px 8px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap'>
                    <strong>{$mto}</strong><br>
                    <span style='color:#6c757d;font-size:.85em'>{$pct}% del saldo</span>
                </td>
                <td style='padding:6px 8px;border-bottom:1px solid #eee;font-size:.85em;color:#495057'>{$cta}</td>
            </tr>";
        })->implode('');

        $totalFmt = $monedaPago . ' ' . number_format($total, 2, ',', '.');
        $cantidad = count($resumen);

        $html = "
            <div style='text-align:left'>
                <div style='background:#f8f9fa;border-radius:6px;padding:10px;margin-bottom:12px;display:flex;justify-content:space-between;gap:12px'>
                    <span>{$cantidad} pago(s) registrado(s)</span>
                    <strong style='color:#198754'>Total: {$totalFmt}</strong>
                </div>
                <table style='width:100%;border-collapse:collapse;font-size:.9rem'>
                    <thead>
                        <tr style='background:#e9ecef'>
                            <th style='padding:6px 8px;text-align:left'>Proveedor / Contrato</th>
                            <th style='padding:6px 8px;text-align:right'>Monto</th>
                            <th style='padding:6px 8px;text-align:left'>Cuenta destino</th>
                        </tr>
                    </thead>
                    <tbody>{$filas}</tbody>
                </table>
            </div>";

        Alert::success('Pagos registrados', $html)->toHtml()->width('46rem');

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
