<?php

namespace App\Http\Controllers;

use App\Models\LotePago;
use App\Models\Movimiento;
use App\Models\PagoCamion;
use App\Models\PagoProveedor;
use App\Models\PagoCliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('lotes_pago.index', compact('lotes'));
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

        $request->validate([
            'codigos'                 => ['required', 'array', 'min:1'],
            'codigos.*.pago_uuid'     => ['required', 'string'],
            'codigos.*.codigo_real'   => ['nullable', 'string', 'max:100'],
        ]);

        $filas = collect($request->input('codigos'))->filter(fn($f) => filled($f['codigo_real'] ?? null));

        if ($filas->isEmpty()) {
            Alert::error('Nada que guardar', 'No ingresaste ningún código.');
            return back();
        }

        $pagos = $modeloClase::where('lote_pago_id', $lote->id)
            ->whereIn('uuid', $filas->pluck('pago_uuid'))
            ->get()
            ->keyBy('uuid');

        foreach ($filas as $fila) {
            $pago = $pagos->get($fila['pago_uuid']);
            if (!$pago) continue;

            $codigoReal = trim($fila['codigo_real']);
            if (!$this->codigoDisponible($codigoReal, $pago->id, $modeloClase)) {
                Alert::error('Código en uso', "El código \"{$codigoReal}\" ya está en uso por otro pago o lote. Verifique e intente de nuevo.");
                return back();
            }
        }

        DB::transaction(function () use ($filas, $pagos, $modeloClase) {
            foreach ($filas as $fila) {
                $pago = $pagos->get($fila['pago_uuid']);
                if (!$pago) continue;

                $codigoReal = trim($fila['codigo_real']);
                $pago->update(['codigo_seguimiento' => $codigoReal]);

                Movimiento::where('origen_type', $modeloClase)
                    ->where('origen_id', $pago->id)
                    ->update(['codigo_seguimiento' => $codigoReal]);
            }
        });

        Alert::success('Éxito', 'Código real actualizado en ' . $filas->count() . ' pago(s).');
        return back();
    }

    /**
     * Detalle de los pagos que se eliminarían junto con el lote — se
     * muestra en el modal de confirmación antes de borrar.
     */
    public function detalle($uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        $pagos = match ($lote->tipo) {
            'proveedor' => $lote->pagosProveedor()->with('contrato.proveedor')->get()->map(fn($p) => [
                'uuid'        => $p->uuid,
                'referencia'  => $p->contrato?->proveedor?->nombre ?? ('Contrato #' . $p->contrato_id),
                'monto'       => (float) $p->monto,
                'moneda'      => $p->moneda_pago,
                'fecha'       => $p->fecha_pago->format('d/m/Y'),
                'codigo'      => $p->codigo_seguimiento,
            ]),
            'cliente' => $lote->pagosCliente()->with('tramo.cliente')->get()->map(fn($p) => [
                'uuid'        => $p->uuid,
                'referencia'  => $p->tramo?->cliente?->nombre ?? ('Tramo #' . $p->tramo_id),
                'monto'       => (float) $p->monto,
                'moneda'      => $p->moneda_pago,
                'fecha'       => $p->fecha_pago->format('d/m/Y'),
                'codigo'      => $p->codigo_seguimiento,
            ]),
            default => $lote->pagosCamion()->with('receptor')->get()->map(fn($p) => [
                'uuid'        => $p->uuid,
                'referencia'  => $p->receptor?->nombre ?? ucfirst($p->receptor_type ?? 'Camión'),
                'monto'       => (float) $p->monto,
                'moneda'      => $p->moneda_pago,
                'fecha'       => $p->fecha_pago->format('d/m/Y'),
                'codigo'      => $p->codigo_seguimiento,
            ]),
        };

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
            $nombre     = $lote->tipo === 'proveedor'
                ? ($cta?->nombre_titular ?: ($p->contrato?->proveedor?->nombre ?? ''))
                : ($cta?->nombre_titular ?: ($p->receptor?->nombre ?? ''));
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
