<?php

namespace App\Http\Controllers;

use App\Models\LotePago;
use App\Models\Movimiento;
use App\Models\PagoCamion;
use App\Models\PagoProveedor;
use App\Models\PagoCliente;
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

    public function index()
    {
        $lotes = LotePago::with(['cuentaOrigen.empresa'])
            ->orderByDesc('created_at')
            ->paginate(30);

        // Resumen de a qué contratos/referencias y montos corresponde cada
        // lote, para mostrarlo directo en la tabla sin abrir el detalle.
        $lotes->getCollection()->transform(function ($lote) {
            $pagos = $this->pagosDelLote($lote);
            $lote->resumen_referencias = $pagos->pluck('referencia')->unique()->values();
            $lote->resumen_monto       = round($pagos->sum('monto'), 2);
            $lote->resumen_moneda      = $pagos->first()['moneda'] ?? 'BOB';
            return $lote;
        });

        return view('lotes_pago.index', compact('lotes'));
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
            ]),
        };
    }

    /**
     * El código provisional del lote sigue siendo el identificador que agrupa
     * los pagos como "un mismo pago masivo". El código real, en cambio, lo da
     * el banco por CADA transferencia individual — así que se edita pago por
     * pago, no de una sola vez para todo el lote.
     *
     * Espera 'codigos' => [ ['pago_uuid' => ..., 'codigo_real' => ...], ... ].
     * Filas con codigo_real vacío se ignoran (ese pago queda pendiente).
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
            'codigos.*.voucher'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'codigos.*.voucher.mimes' => 'El comprobante debe ser JPG, PNG o PDF.',
            'codigos.*.voucher.max'   => 'El comprobante no debe superar los 5 MB.',
        ]);

        $filas = collect($request->input('codigos'))->map(function ($f, $i) use ($request) {
            $f['voucher'] = $request->file("codigos.$i.voucher");
            return $f;
        })->filter(fn($f) => filled($f['codigo_real'] ?? null) || $f['voucher']);

        if ($filas->isEmpty()) {
            Alert::error('Nada que guardar', 'No ingresaste ningún código ni adjuntaste un voucher.');
            return back();
        }

        $pagos = $modeloClase::where('lote_pago_id', $lote->id)
            ->whereIn('uuid', $filas->pluck('pago_uuid'))
            ->get()
            ->keyBy('uuid');

        foreach ($filas as $fila) {
            $pago = $pagos->get($fila['pago_uuid']);
            if (!$pago || !filled($fila['codigo_real'] ?? null)) continue;

            $codigoReal = trim($fila['codigo_real']);
            if (!$this->codigoDisponible($codigoReal, $pago->id, $modeloClase)) {
                Alert::error('Código en uso', "El código \"{$codigoReal}\" ya está en uso por otro pago o lote. Verifique e intente de nuevo.");
                return back();
            }
        }

        DB::transaction(function () use ($filas, $pagos, $modeloClase, $carpetaVoucher) {
            foreach ($filas as $fila) {
                $pago = $pagos->get($fila['pago_uuid']);
                if (!$pago) continue;

                $datos = [];
                if (filled($fila['codigo_real'] ?? null)) {
                    $datos['codigo_seguimiento'] = trim($fila['codigo_real']);
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

                if (!isset($datos['codigo_seguimiento'])) continue;

                Movimiento::where('origen_type', $modeloClase)
                    ->where('origen_id', $pago->id)
                    ->update(['codigo_seguimiento' => $datos['codigo_seguimiento']]);
            }
        });

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

        Alert::success('Éxito', 'Fecha actualizada en el lote, sus pagos y movimientos.');
        return back();
    }

    /**
     * Detalle de los pagos que se eliminarían junto con el lote — se
     * muestra en el modal de confirmación antes de borrar.
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

        $filas = $pagos->values()->map(function ($p, $i) use ($lote) {
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
                $esGanadero ? 0 : ($cta?->sucursal_departamento ?? ''),
                $glosa,
                $p->codigo_seguimiento ?? '',
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
    public function destroy($uuid)
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

        Alert::success('Éxito', 'Lote eliminado: todos sus pagos y movimientos en tesorería fueron revertidos.');
        return redirect()->route('lotes_pago.index');
    }
}
