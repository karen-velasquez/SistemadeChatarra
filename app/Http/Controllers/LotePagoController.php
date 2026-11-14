<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\ContratoCamion;
use App\Models\CuentaBancaria;
use App\Models\LotePago;
use App\Models\Movimiento;
use App\Models\PagoCamion;
use App\Models\PagoProveedor;
use App\Models\PagoCliente;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class LotePagoController extends Controller
{
    use \App\Http\Controllers\Concerns\GeneraCodigoSeguimientoUnico;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $tipo = $request->input('tipo');
        $numeroContrato = trim((string) $request->input('numero_contrato'));

        $query = LotePago::with(['cuentaOrigen.empresa'])
            ->orderByDesc('created_at');

        if (in_array($tipo, ['proveedor', 'cliente', 'camion'])) {
            $query->where('tipo', $tipo);
        }

        if ($numeroContrato !== '') {
            $query->where(function ($q) use ($numeroContrato) {
                $q->whereHas('pagosProveedor.contrato', fn($c) => $c->where('numero_contrato', 'like', "%{$numeroContrato}%"))
                  ->orWhereHas('pagosCliente.tramo.contratoCamion.contrato', fn($c) => $c->where('numero_contrato', 'like', "%{$numeroContrato}%"))
                  ->orWhereHas('pagosCamion.contratoCamion.contrato', fn($c) => $c->where('numero_contrato', 'like', "%{$numeroContrato}%"));
            });
        }

        $lotes = $query->paginate(30)->withQueryString();

        // Resumen de a qué contratos/referencias y montos corresponde cada
        // lote, para mostrarlo directo en la tabla sin abrir el detalle.
        $lotes->getCollection()->transform(function ($lote) {
            $pagos = $this->pagosDelLote($lote);
            $lote->resumen_referencias = $pagos->pluck('referencia')->unique()->values();
            $lote->resumen_monto       = round($pagos->sum('monto'), 2);
            $lote->resumen_moneda      = $pagos->first()['moneda'] ?? 'BOB';
            return $lote;
        });

        return view('lotes_pago.index', compact('lotes', 'tipo', 'numeroContrato'));
    }

    /**
     * Pagos del lote con su referencia (a qué contrato/proveedor/cliente
     * corresponde) y monto. Fuente única para el resumen de la tabla y
     * para el modal de detalle.
     */
    private function pagosDelLote(LotePago $lote)
    {
        return match ($lote->tipo) {
            'proveedor' => $lote->pagosProveedor()->with('contrato.proveedor')->get()->map(fn($p) => [
                'uuid'          => $p->uuid,
                'referencia'    => trim(($p->contrato?->numero_contrato ?? ('#' . $p->contrato_id)) . ' — ' . ($p->contrato?->proveedor?->nombre ?? '')),
                'monto'         => (float) $p->monto,
                'moneda'        => $p->moneda_pago,
                'fecha'         => $p->fecha_pago->format('d/m/Y'),
                'codigo'        => $p->codigo_seguimiento,
                'tiene_voucher' => (bool) $p->voucher,
                'voucher_url'   => $p->voucher ? route('pagos.proveedores.voucher', $p->uuid) : null,
                'voucher_es_imagen' => es_imagen_archivo($p->voucher),
            ]),
            'cliente' => $lote->pagosCliente()->with('tramo.cliente', 'tramo.contratoCamion.contrato')->get()->map(fn($p) => [
                'uuid'          => $p->uuid,
                'referencia'    => trim(($p->tramo?->contratoCamion?->contrato?->numero_contrato ?? ('Tramo #' . $p->tramo_id)) . ' — ' . ($p->tramo?->cliente?->nombre ?? '')),
                'monto'         => (float) $p->monto,
                'moneda'        => $p->moneda_pago,
                'fecha'         => $p->fecha_pago->format('d/m/Y'),
                'codigo'        => $p->codigo_seguimiento,
                'tiene_voucher' => (bool) $p->voucher,
                'voucher_url'   => $p->voucher ? route('pagos.clientes.voucher', $p->uuid) : null,
                'voucher_es_imagen' => es_imagen_archivo($p->voucher),
            ]),
            default => $lote->pagosCamion()->with('receptor', 'contratoCamion.contrato')->get()->map(fn($p) => [
                'uuid'          => $p->uuid,
                'referencia'    => trim(($p->contratoCamion?->contrato?->numero_contrato ?? '') . ' — ' . ($p->receptor?->nombre ?? ucfirst($p->receptor_type ?? 'Camión'))),
                'monto'         => (float) $p->monto,
                'moneda'        => $p->moneda_pago,
                'fecha'         => $p->fecha_pago->format('d/m/Y'),
                'codigo'        => $p->codigo_seguimiento,
                'tiene_voucher' => (bool) $p->voucher,
                'voucher_url'   => $p->voucher ? route('pagos.camiones.voucher', $p->uuid) : null,
                'voucher_es_imagen' => es_imagen_archivo($p->voucher),
            ]),
        };
    }

    /**
     * Confirma que el banco ya ejecutó el lote: recién aquí se crean los
     * movimientos de tesorería (y se descuenta el saldo de la cuenta origen).
     * Antes de confirmar, el pago masivo es solo una orden generada — ya
     * descuenta el saldo pendiente del contrato/flete, pero no afecta la
     * cuenta bancaria real hasta que se confirme. Solo aplica a proveedor y
     * camión: cobro masivo a clientes no pasa por este flujo.
     * realizando cambios
     * 
     */
    public function confirmar(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        abort_if($lote->tipo === 'cliente', 404);
        if ($lote->estado === 'confirmado') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Este lote ya fue confirmado.'], 400);
            }
            abort(400, 'Este lote ya fue confirmado.');
        }

        $categoria = $lote->tipo === 'proveedor' ? 'pago_proveedor' : 'pago_camion';
        $pagos     = $lote->tipo === 'proveedor'
            ? $lote->pagosProveedor()->with('contrato.proveedor')->get()
            : $lote->pagosCamion()->with('receptor', 'contratoCamion.contrato.proveedor', 'contratoCamion.camion')->get();

        DB::transaction(function () use ($lote, $categoria, $pagos) {
            foreach ($pagos as $pago) {
                $conceptoDetalle = match ($lote->tipo) {
                    'proveedor' => 'Pago masivo proveedor: ' . ($pago->contrato?->proveedor?->nombre ?? 'Proveedor')
                        . ($pago->contrato?->numero_contrato ? ' - Contrato ' . $pago->contrato->numero_contrato : ''),
                    default => 'Pago flete: ' . ($pago->contratoCamion?->camion?->placa ?? '—')
                        . ' — ' . ($pago->contratoCamion?->contrato?->proveedor?->nombre ?? '—')
                        . ' (' . ($pago->contratoCamion?->contrato?->numero_contrato ?? '—') . ')',
                };

                Movimiento::registrarDePago($pago, 'egreso', $categoria, $lote->cuenta_origen_id, $conceptoDetalle, $pago->observaciones);
            }

            $lote->update(['estado' => 'confirmado']);
        });

        $mensaje = 'Lote confirmado: se registraron ' . $pagos->count() . ' movimiento(s) en tesorería.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje]);
        }
        Alert::success('Éxito', $mensaje);
        return back();
    }

    /**
     * El código provisional del lote sigue siendo el identificador que agrupa
     * los pagos como "un mismo pago masivo". El código real, en cambio, lo da
     * el banco por CADA transferencia individual — así que se edita pago por
     * pago, no de una sola vez para todo el lote.
     *
     * Espera 'codigos' => [ ['pago_uuid' => ..., 'codigo_real' => ...], ... ].
     * Filas con codigo_real vacío se ignoran (ese pago queda pendiente).
     * ------------------------------------------------------
     */
    public function actualizarCodigo(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();
        $modeloClase = $this->modeloDelLote($lote);
        $carpetaVoucher = 'vouchers_pago_' . $lote->tipo;

        $request->validate([
            'codigos'                 => ['required', 'array', 'min:1'],
            'codigos.*.pago_uuid'     => ['required', 'string'],
            'codigos.*.codigo_real'   => ['nullable', 'string', 'max:100'],
            'codigos.*.monto'         => ['nullable', 'numeric', 'min:0.01'],
            'codigos.*.voucher'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'codigos.*.voucher.mimes' => 'El comprobante debe ser JPG, PNG o PDF.',
            'codigos.*.voucher.max'   => 'El comprobante no debe superar los 5 MB.',
            'codigos.*.monto.min'     => 'El monto debe ser mayor a cero.',
        ]);

        // El monto solo se puede corregir mientras el lote sigue pendiente: una
        // vez confirmado ya generó movimientos de tesorería con ese monto, y
        // cambiarlo después dejaría esos movimientos desincronizados.
        $permiteEditarMonto = $lote->estado === 'pendiente';

        $filas = collect($request->input('codigos'))->map(function ($f, $i) use ($request) {
            $f['voucher'] = $request->file("codigos.$i.voucher");
            return $f;
        })->filter(fn($f) => filled($f['codigo_real'] ?? null) || $f['voucher'] || filled($f['monto'] ?? null));

        if ($filas->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No ingresaste ningún código, monto ni adjuntaste un voucher.'], 400);
            }
            Alert::error('Nada que guardar', 'No ingresaste ningún código ni adjuntaste un voucher.');
            return back();
        }

        // Todos los pagos hermanos del mismo lote se excluyen del chequeo de
        // unicidad: es válido que varios pagos de un mismo lote compartan el
        // mismo código real (una sola transferencia del banco puede cubrir
        // varios contratos/tramos a la vez). El conflicto real que importa
        // detectar es contra pagos de OTROS lotes.
        // ------------------------------------------
        $idsDelLote = $modeloClase::where('lote_pago_id', $lote->id)
            ->pluck('id')->all();

        $pagos = $modeloClase::where('lote_pago_id', $lote->id)
            ->whereIn('uuid', $filas->pluck('pago_uuid'))
            ->get()
            ->keyBy('uuid');

        foreach ($filas as $fila) {
            $pago = $pagos->get($fila['pago_uuid']);
            if (!$pago || !filled($fila['codigo_real'] ?? null)) continue;

            $codigoReal = trim($fila['codigo_real']);
            if (!$this->codigoDisponible($codigoReal, $pago->id, $modeloClase, $lote->id, $idsDelLote)) {
                $mensaje = "El código \"{$codigoReal}\" ya está en uso por otro pago o lote. Verifique e intente de nuevo.";
                if ($request->expectsJson()) {
                    return response()->json(['message' => $mensaje], 400);
                }
                Alert::error('Código en uso', $mensaje);
                return back();
            }
        }

        $pagosActualizados = DB::transaction(function () use ($filas, $pagos, $modeloClase, $carpetaVoucher, $permiteEditarMonto) {
            $actualizados = [];
            foreach ($filas as $fila) {
                $pago = $pagos->get($fila['pago_uuid']);
                if (!$pago) continue;

                $datos = [];
                if (filled($fila['codigo_real'] ?? null)) {
                    $datos['codigo_seguimiento'] = trim($fila['codigo_real']);
                }
                if ($permiteEditarMonto && filled($fila['monto'] ?? null) && round((float) $fila['monto'], 2) !== round((float) $pago->monto, 2)) {
                    $datos['monto'] = round((float) $fila['monto'], 2);
                }
                if ($fila['voucher']) {
                    $anterior = $pago->voucher;
                    $datos['voucher'] = $fila['voucher']->store($carpetaVoucher, 'public');
                    if ($anterior) {
                        Storage::disk('public')->delete($anterior);
                    }
                }
                if (empty($datos)) continue;

                $pago->update($datos);
                $actualizados[] = $pago;

                if (!isset($datos['codigo_seguimiento'])) continue;

                Movimiento::where('origen_type', $modeloClase)
                    ->where('origen_id', $pago->id)
                    ->update(['codigo_seguimiento' => $datos['codigo_seguimiento']]);
            }
            return $actualizados;
        });

        if ($request->expectsJson()) {
            if (empty($pagosActualizados)) {
                return response()->json(['message' => 'No había ningún dato nuevo que guardar en este pago.'], 400);
            }

            $voucherRouteName = match ($lote->tipo) {
                'proveedor' => 'pagos.proveedores.voucher',
                'cliente'   => 'pagos.clientes.voucher',
                default     => 'pagos.camiones.voucher',
            };

            return response()->json([
                'message' => count($pagosActualizados) . ' pago(s) actualizado(s).',
                'pagos'   => collect($pagosActualizados)->map(fn($p) => [
                    'uuid'              => $p->uuid,
                    'monto'             => (float) $p->monto,
                    'codigo'            => $p->codigo_seguimiento,
                    'tiene_voucher'     => (bool) $p->voucher,
                    'voucher_url'       => $p->voucher ? route($voucherRouteName, $p->uuid) : null,
                    'voucher_es_imagen' => es_imagen_archivo($p->voucher),
                ]),
            ]);
        }

        Alert::success('Éxito', 'Datos actualizados en ' . $filas->count() . ' pago(s).');
        return back();
    }

    /**
     * Corrige la fecha del lote. Se propaga a los pagos individuales y a los
     * movimientos de tesorería asociados para que todo quede consistente,
     * igual criterio que actualizarCodigo() con el código real.
     */
    public function actualizarFecha(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();
        $modeloClase = $this->modeloDelLote($lote);

        $request->validate([
            'fecha_pago' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($request, $lote, $modeloClase) {
            $lote->update(['fecha_pago' => $request->fecha_pago]);

            $modeloClase::where('lote_pago_id', $lote->id)
                ->update(['fecha_pago' => $request->fecha_pago]);

            Movimiento::where('lote_pago_id', $lote->id)
                ->update(['fecha' => $request->fecha_pago]);
        });

        $mensaje = 'Fecha actualizada en el lote, sus pagos y movimientos.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje]);
        }
        Alert::success('Éxito', $mensaje);
        return back();
    }

    /**
     * Detalle de los pagos que se eliminarían junto con el lote — se
     * muestra en el modal de confirmación antes de borrar.
     * /-----------------------------------------
     */
    public function detalle($uuid)
    {
        $lote  = LotePago::where('uuid', $uuid)->firstOrFail();
        $pagos = $this->pagosDelLote($lote);

        return response()->json([
            'tipo'        => $lote->tipo,
            'total_pagos' => $pagos->count(),
            'total_monto' => round($pagos->sum('monto'), 2),
            'pagos'       => $pagos->values(),
        ]);
    }

    /**
     * Reconstruye el Excel formato banco (mismas 16 columnas del pop-up de
     * Pago Masivo) a partir de los pagos ya guardados del lote, usando la
     * cuenta bancaria de destino (cuenta_destino_id) tal como quedó en cada
     * pago al momento de registrarlo. Solo aplica a lotes 'proveedor' y
     * 'camion' — cobro masivo a clientes nunca generó este formato.
     */
    public function excel($uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        abort_if($lote->tipo === 'cliente', 404);

        $pagos = $lote->tipo === 'proveedor'
            ? $lote->pagosProveedor()->with(['contrato', 'cuentaDestino.banco'])->orderBy('id')->get()
            : $lote->pagosCamion()->with(['receptor', 'cuentaDestino.banco'])->orderBy('id')->get();

        // Mismo mapeo que usa el pop-up de Pago Masivo: la sucursal va abreviada
        // (sigla), no el nombre completo del departamento.
        $sucMap = Parametro::where('tipo', 'sucursal_cuenta')->whereNull('deleted_at')->pluck('valor', 'descripcion');

        $filas = $pagos->values()->map(function ($p, $i) use ($lote, $sucMap) {
            $cta        = $p->cuentaDestino;
            $banco      = $cta?->banco?->nombre ?? '';
            $esGanadero = $banco === 'Banco Ganadero';
            // nombre_titular_excel arma "APELLIDO_PATERNO APELLIDO_MATERNO NOMBRE" (o el
            // nombre completo de la entidad relacionada si no hay nombre manual en la
            // cuenta) — mismo formato que usa el pop-up de Pago Masivo. Usar el campo
            // plano nombre_titular aquí (sin el accessor) dejaba el nombre incompleto.
            $nombre     = $cta?->nombre_titular_excel
                ?: ($lote->tipo === 'proveedor'
                    ? ($p->contrato?->proveedor?->nombre ?? '')
                    : ($p->receptor?->nombre ?? ''));
            $glosa      = $lote->tipo === 'proveedor'
                ? 'Pago proveedor ' . ($p->contrato?->numero_contrato ?? '#' . $p->contrato_id)
                : '';

            return [
                $i + 1,
                0,
                $cta?->numero_cuenta ?? '',
                $nombre,
                $cta?->nro_documento ?? '',
                number_format((float) $p->monto, 2, ',', ''),
                $p->fecha_pago->format('d/m/Y'),
                $esGanadero ? 1 : 3,
                $esGanadero ? 0 : ($p->moneda_pago === 'USD' ? 2 : 1),
                $esGanadero ? 0 : ($cta?->banco?->codigo_banco ?? ''),
                $esGanadero ? 0 : ($sucMap[$cta?->sucursal_departamento] ?? ''),
                $glosa,
                // El código único lo completa el banco: va vacío igual que en el
                // pop-up de Pago Masivo, no el código de seguimiento del sistema.
                '',
                $cta?->email_notificacion ?? '',
                '',
                '',
            ];
        });

        return response()->json([
            'cols' => ['NRO DE ORDEN','CODIGO DE CLIENTE','NRO DE CUENTA','NOMBRE DEL CLIENTE','DOC DE IDENTIDAD','IMPORTE','FECHA DE PAGO','FORMA DE PAGO','MONEDA DESTINO','ENTIDAD DESTINO','SUCURSAL DESTINO','GLOSA','CODIGO UNICO','EMAIL NOTIFICACION','NRO_DOC_TERCERO','NOMBRE TERCERO'],
            'filas' => $filas,
            'total' => round($pagos->sum('monto'), 2),
            'moneda' => $pagos->first()?->moneda_pago ?? 'BOB',
        ]);
    }

    private function modeloDelLote(LotePago $lote): string
    {
        return match ($lote->tipo) {
            'proveedor' => PagoProveedor::class,
            'cliente'   => PagoCliente::class,
            default     => PagoCamion::class,
        };
    }

    /**
     * Elimina el lote completo: todos sus pagos y los movimientos de
     * tesorería asociados. Mismo patrón que el destroy de un pago
     * individual (PagoProveedorController/PagoClienteController/
     * PagoCamionController), pero iterado a todos los pagos del lote.
     * El delete() de cada Movimiento dispara su hook que revierte el
     * saldo de la cuenta empresa afectada.
     */
    /**
     * Contratos de proveedor con saldo pendiente, para el selector de
     * "agregar pago" — cualquier proveedor, no solo los que ya tiene el
     * lote. Se excluyen los que ya están en este mismo lote (no tendría
     * sentido duplicar el contrato dentro del mismo lote).
     */
    public function contratosDisponibles($uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();
        abort_if($lote->tipo !== 'proveedor', 404);

        // Incluye también los contratos que ya tienen un pago en este lote: puede
        // agregarse un segundo pago al mismo contrato (p.ej. dos transferencias
        // distintas para la misma deuda). El saldo pendiente ya descuenta esos
        // pagos existentes, así que no deja registrar de más.
        $contratos = Contrato::with('proveedor')
            ->whereNull('deleted_at')
            ->whereNotNull('proveedor_id')
            ->get()
            ->filter(fn($c) => $c->saldo_pendiente_proveedor > 0)
            ->map(fn($c) => [
                'id'               => $c->id,
                'label'            => $c->numero_contrato . ' — ' . ($c->proveedor->nombre ?? '—'),
                'saldo_pendiente'  => round($c->saldo_pendiente_proveedor, 2),
                'moneda'           => $c->moneda ?? 'BOB',
                'proveedor_id'     => $c->proveedor_id,
            ])
            ->sortBy('label')
            ->values();

        $cuentasPorProveedor = CuentaBancaria::with('banco')
            ->whereNull('deleted_at')
            ->where('titular_type', 'App\Models\Proveedor')
            ->whereIn('titular_id', $contratos->pluck('proveedor_id')->unique())
            ->get()
            ->groupBy('titular_id')
            ->map(fn($cuentas) => $cuentas->map(fn($c) => [
                'id'    => $c->id,
                'label' => ($c->banco->nombre ?? '—') . ' · ' . $c->numero_cuenta . ' (' . $c->moneda . ')',
            ])->values());

        return response()->json([
            'contratos'           => $contratos,
            'cuentas_por_proveedor' => $cuentasPorProveedor,
        ]);
    }

    /**
     * Agrega un pago nuevo a un lote de proveedor ya existente, mientras
     * siga pendiente. Hereda cuenta origen, fecha y método del lote (igual
     * que los pagos creados en el pago masivo original); el código de
     * seguimiento se deja vacío para completarse luego desde "Editar lote".
     */
    public function agregarPago(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        abort_if($lote->tipo !== 'proveedor', 404);
        abort_if($lote->estado !== 'pendiente', 400, 'Solo se pueden agregar pagos a un lote pendiente.');

        $request->validate([
            'contrato_id'       => 'required|exists:contratos,id',
            'monto'             => 'required|numeric|min:0.01',
            'cuenta_destino_id' => 'required|exists:cuentas_bancarias,id',
        ]);

        $contrato = Contrato::with('proveedor')->findOrFail($request->contrato_id);
        $saldo = $contrato->saldo_pendiente_proveedor;
        $monto = round((float) $request->monto, 2);

        if ($monto > round($saldo, 2)) {
            return response()->json(['message' => 'El monto supera el saldo pendiente del contrato (' . number_format($saldo, 2) . ').'], 400);
        }

        $cuentaOrigen = \App\Models\CuentaEmpresa::findOrFail($lote->cuenta_origen_id);
        $monedaPago = $cuentaOrigen->moneda ?? 'BOB';

        $pago = DB::transaction(function () use ($lote, $contrato, $monto, $monedaPago, $request) {
            $tipoPago = $monto >= round($contrato->saldo_pendiente_proveedor, 2) ? 'pago_final' : 'adelanto';

            return PagoProveedor::create([
                'lote_pago_id'       => $lote->id,
                'contrato_id'        => $contrato->id,
                'tipo_pago'          => $tipoPago,
                'monto'              => $monto,
                'moneda_pago'        => $monedaPago,
                'tipo_cambio'        => 1,
                'fecha_pago'         => $lote->fecha_pago,
                'metodo_pago'        => $lote->metodo_pago,
                'codigo_seguimiento' => null,
                'cuenta_origen_id'   => $lote->cuenta_origen_id,
                'cuenta_destino_id'  => $request->cuenta_destino_id,
                'observaciones'      => 'Pago agregado al lote (lote ' . ($lote->codigo_provisional ?: $lote->uuid) . ').',
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);
        });

        return response()->json([
            'message' => 'Pago agregado al lote.',
            'pago' => [
                'uuid'          => $pago->uuid,
                'referencia'    => trim($contrato->numero_contrato . ' — ' . ($contrato->proveedor->nombre ?? '')),
                'monto'         => (float) $pago->monto,
                'moneda'        => $pago->moneda_pago,
                'fecha'         => $pago->fecha_pago->format('d/m/Y'),
                'codigo'        => null,
                'tiene_voucher' => false,
                'voucher_url'   => null,
                'voucher_es_imagen' => false,
            ],
        ]);
    }

    /**
     * Fletes (ContratoCamion) con saldo pendiente, para el selector de
     * "agregar pago" de un lote de camión — cualquier proveedor, excluyendo
     * los fletes que ya están en este mismo lote. Las cuentas bancarias se
     * agrupan por flete (no por operador global) porque cada flete tiene su
     * propio conductor/propietario con sus propias cuentas.
     * ---------------------------------
     */
    public function fletesDisponibles($uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();
        abort_if($lote->tipo !== 'camion', 404);

        // Incluye también los fletes que ya tienen un pago en este lote: puede
        // agregarse un segundo pago al mismo flete. El saldo pendiente ya
        // descuenta esos pagos existentes, así que no deja registrar de más.
        $fletes = ContratoCamion::with(['contrato.proveedor', 'camion.propietario', 'conductor'])
            ->whereNotNull('monto_acordado')
            ->get()
            ->filter(fn($cc) => $cc->saldo_pendiente > 0)
            ->map(function ($cc) {
                $operadorIds = array_filter([$cc->conductor_id, $cc->camion?->propietario_id]);
                $cuentas = CuentaBancaria::with('banco')
                    ->whereNull('deleted_at')
                    ->whereIn('titular_id', $operadorIds)
                    ->where('titular_type', 'App\Models\OperadorTransporte')
                    ->get()
                    ->map(fn($c) => [
                        'id'    => $c->id,
                        'label' => ($c->nombre_titular_cuenta ? $c->nombre_titular_cuenta . ' — ' : '')
                            . ($c->banco->nombre ?? '') . ' ' . $c->numero_cuenta
                            . ($c->alias ? ' (' . $c->alias . ')' : ''),
                    ])
                    ->values();

                return [
                    'id'              => $cc->id,
                    'label'           => ($cc->camion?->placa ?? '—') . ' — ' . ($cc->contrato?->numero_contrato ?? '—')
                        . ' — ' . ($cc->contrato?->proveedor?->nombre ?? '—'),
                    'saldo_pendiente' => round($cc->saldo_pendiente, 2),
                    'cuentas'         => $cuentas,
                ];
            })
            ->sortBy('label')
            ->values();

        return response()->json(['fletes' => $fletes]);
    }

    /**
     * Agrega un pago nuevo a un lote de camión (flete) ya existente, mientras
     * siga pendiente. El receptor (conductor o propietario) se deduce de la
     * cuenta destino elegida, igual que en el pago masivo original; hereda
     * cuenta origen, fecha y método del lote.
     */
    public function agregarPagoFlete(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        abort_if($lote->tipo !== 'camion', 404);
        abort_if($lote->estado !== 'pendiente', 400, 'Solo se pueden agregar pagos a un lote pendiente.');

        $request->validate([
            'contrato_camion_id' => 'required|exists:contrato_camiones,id',
            'monto'              => 'required|numeric|min:0.01',
            'cuenta_destino_id'  => 'nullable|exists:cuentas_bancarias,id',
        ]);

        $cc = ContratoCamion::with(['contrato.proveedor', 'camion'])->findOrFail($request->contrato_camion_id);
        $saldo = $cc->saldo_pendiente;
        $monto = round((float) $request->monto, 2);

        if ($monto > round($saldo, 2)) {
            return response()->json(['message' => 'El monto supera el saldo pendiente del flete (' . number_format($saldo, 2) . ').'], 400);
        }

        $cuentaOrigen = \App\Models\CuentaEmpresa::findOrFail($lote->cuenta_origen_id);
        $monedaPago = $cuentaOrigen->moneda ?? 'BOB';

        $receptorType = null;
        $receptorId = null;
        if ($request->cuenta_destino_id) {
            $ctaDest = CuentaBancaria::find($request->cuenta_destino_id);
            if ($ctaDest && $ctaDest->titular_id && $ctaDest->titular_type) {
                $receptorType = $ctaDest->titular_type;
                $receptorId = $ctaDest->titular_id;
            }
        }

        $pago = DB::transaction(function () use ($lote, $cc, $monto, $monedaPago, $request, $receptorType, $receptorId) {
            $tipoPago = $monto >= round($cc->saldo_pendiente, 2) ? 'pago_final' : 'adelanto';

            return PagoCamion::create([
                'lote_pago_id'       => $lote->id,
                'contrato_camion_id' => $cc->id,
                'tipo_pago'          => $tipoPago,
                'monto'              => $monto,
                'moneda_pago'        => $monedaPago,
                'tipo_cambio'        => 1,
                'fecha_pago'         => $lote->fecha_pago,
                'metodo_pago'        => $lote->metodo_pago,
                'codigo_seguimiento' => null,
                'cuenta_origen_id'   => $lote->cuenta_origen_id,
                'cuenta_destino_id'  => $request->cuenta_destino_id ?: null,
                'receptor_type'      => $receptorType,
                'receptor_id'        => $receptorId,
                'observaciones'      => 'Pago agregado al lote (lote ' . ($lote->codigo_provisional ?: $lote->uuid) . ').',
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);
        });

        return response()->json([
            'message' => 'Pago agregado al lote.',
            'pago' => [
                'uuid'          => $pago->uuid,
                'referencia'    => trim(($cc->camion?->placa ?? '—') . ' — ' . ($cc->contrato?->numero_contrato ?? '—')),
                'monto'         => (float) $pago->monto,
                'moneda'        => $pago->moneda_pago,
                'fecha'         => $pago->fecha_pago->format('d/m/Y'),
                'codigo'        => null,
                'tiene_voucher' => false,
                'voucher_url'   => null,
                'voucher_es_imagen' => false,
            ],
        ]);
    }

    /**
     * Quita un solo pago del lote (no todo el lote). Solo mientras el lote
     * sigue pendiente: si ya fue confirmado, ya generó su movimiento de
     * tesorería y descontó saldo, así que quitarlo después dejaría todo
     * desincronizado — mismo criterio que la edición de montos.
     */
    public function quitarPago($uuid, $pagoUuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        abort_if($lote->estado !== 'pendiente', 400, 'Solo se pueden quitar pagos de un lote pendiente.');

        $modeloClase = $this->modeloDelLote($lote);
        $pago = $modeloClase::where('lote_pago_id', $lote->id)->where('uuid', $pagoUuid)->firstOrFail();

        $totalPagos = $modeloClase::where('lote_pago_id', $lote->id)->count();
        if ($totalPagos <= 1) {
            return response()->json(['message' => 'No se puede quitar el último pago del lote. Elimine el lote completo si ya no corresponde.'], 400);
        }

        DB::transaction(function () use ($lote, $pago) {
            if ($pago->voucher) {
                Storage::disk('public')->delete($pago->voucher);
            }
            Movimiento::where('origen_type', get_class($pago))
                ->where('origen_id', $pago->id)
                ->each(fn($m) => $m->delete());
            $pago->delete();
        });

        return response()->json(['message' => 'Pago quitado del lote.']);
    }

    public function destroy(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        DB::transaction(function () use ($lote) {
            Movimiento::where('lote_pago_id', $lote->id)
                ->each(fn($m) => $m->delete());

            $this->modeloDelLote($lote)::where('lote_pago_id', $lote->id)
                ->get()
                ->each(fn($pago) => $pago->delete());

            $lote->delete();
        });

        $mensaje = 'Lote eliminado: todos sus pagos y movimientos en tesorería fueron revertidos.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje]);
        }
        Alert::success('Éxito', $mensaje);
        return redirect()->route('lotes_pago.index');
    }
}
