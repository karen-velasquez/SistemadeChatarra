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
     * Verificación AJAX al hacer clic en Guardar (no en cada tecla), igual
     * patrón que Cobros a Clientes: evita saturar de consultas y solo avisa
     * cuando el usuario ya decidió confirmar el código.
     */
    public function verificarCodigo(Request $request)
    {
        $codigo = trim((string) $request->query('codigo'));
        if ($codigo === '') {
            return response()->json(['disponible' => true]);
        }

        $loteUuid = $request->query('lote_uuid');
        $exceptoLoteId = $loteUuid ? LotePago::where('uuid', $loteUuid)->value('id') : null;

        return response()->json(['disponible' => $this->codigoDisponible($codigo, null, null, $exceptoLoteId)]);
    }

    public function actualizarCodigo(Request $request, $uuid)
    {
        $lote = LotePago::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'codigo_real' => ['required', 'string', 'max:100', function ($attr, $value, $fail) use ($lote) {
                if (!$this->codigoDisponible($value, null, null, $lote->id)) {
                    $fail('Ese código ya está en uso por otro pago o lote. Verifique o ingrese uno distinto.');
                }
            }],
        ], [
            'codigo_real.required' => 'El código de transferencia real es obligatorio.',
        ]);

        $codigoReal = $request->codigo_real;
        $codigoAnterior = $lote->codigo_real ?? $lote->codigo_provisional;

        $lote->update(['codigo_real' => $codigoReal]);

        // Actualizar en todos los pagos del lote
        $this->modeloDelLote($lote)::where('lote_pago_id', $lote->id)
            ->update(['codigo_seguimiento' => $codigoReal]);

        // Actualizar en todos los movimientos del lote
        Movimiento::where('lote_pago_id', $lote->id)
            ->update(['codigo_seguimiento' => $codigoReal]);

        // Las observaciones del pago masivo mencionan el código del lote:
        // deben reflejar el código nuevo, no el provisional ya reemplazado.
        if ($codigoAnterior && $codigoAnterior !== $codigoReal) {
            $this->reemplazarCodigoEnObservaciones($lote, $codigoAnterior, $codigoReal);
        }

        Alert::success('Éxito', "Código actualizado en todos los registros del lote.");
        return back();
    }

    /**
     * Sustituye el código del lote dentro del texto de las observaciones.
     * Se usa REPLACE de SQL para tocar solo esa parte y conservar cualquier
     * nota que el usuario haya escrito junto a ella.
     */
    private function reemplazarCodigoEnObservaciones(LotePago $lote, string $anterior, string $nuevo): void
    {
        foreach ([$this->modeloDelLote($lote), Movimiento::class] as $clase) {
            $clase::where('lote_pago_id', $lote->id)
                ->whereNotNull('observaciones')
                ->where('observaciones', 'like', '%' . $anterior . '%')
                ->update([
                    'observaciones' => DB::raw('REPLACE(observaciones, ' . DB::getPdo()->quote($anterior) . ', ' . DB::getPdo()->quote($nuevo) . ')'),
                ]);
        }
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
                'referencia' => $p->contrato?->proveedor?->nombre ?? ('Contrato #' . $p->contrato_id),
                'monto'      => (float) $p->monto,
                'moneda'     => $p->moneda_pago,
                'fecha'      => $p->fecha_pago->format('d/m/Y'),
            ]),
            'cliente' => $lote->pagosCliente()->with('tramo.cliente')->get()->map(fn($p) => [
                'referencia' => $p->tramo?->cliente?->nombre ?? ('Tramo #' . $p->tramo_id),
                'monto'      => (float) $p->monto,
                'moneda'     => $p->moneda_pago,
                'fecha'      => $p->fecha_pago->format('d/m/Y'),
            ]),
            default => $lote->pagosCamion()->with('receptor')->get()->map(fn($p) => [
                'referencia' => $p->receptor?->nombre ?? ucfirst($p->receptor_type ?? 'Camión'),
                'monto'      => (float) $p->monto,
                'moneda'     => $p->moneda_pago,
                'fecha'      => $p->fecha_pago->format('d/m/Y'),
            ]),
        };

        return response()->json([
            'tipo'        => $lote->tipo,
            'total_pagos' => $pagos->count(),
            'total_monto' => round($pagos->sum('monto'), 2),
            'pagos'       => $pagos->values(),
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
