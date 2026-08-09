<?php

namespace App\Http\Controllers;

use App\Models\LotePago;
use App\Models\Movimiento;
use App\Models\PagoCamion;
use App\Models\PagoProveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class LotePagoController extends Controller
{
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

    public function actualizarCodigo(Request $request, $uuid)
    {
        $request->validate([
            'codigo_real' => 'required|string|max:100',
        ], [
            'codigo_real.required' => 'El código de transferencia real es obligatorio.',
        ]);

        $lote = LotePago::where('uuid', $uuid)->firstOrFail();
        $codigoReal = $request->codigo_real;
        $codigoAnterior = $lote->codigo_real ?? $lote->codigo_provisional;

        $lote->update(['codigo_real' => $codigoReal]);

        // Actualizar en todos los pagos del lote
        if ($lote->tipo === 'proveedor') {
            PagoProveedor::where('lote_pago_id', $lote->id)
                ->update(['codigo_seguimiento' => $codigoReal]);
        } else {
            PagoCamion::where('lote_pago_id', $lote->id)
                ->update(['codigo_seguimiento' => $codigoReal]);
        }

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
        $modelo = $lote->tipo === 'proveedor' ? PagoProveedor::class : PagoCamion::class;

        foreach ([$modelo, Movimiento::class] as $clase) {
            $clase::where('lote_pago_id', $lote->id)
                ->whereNotNull('observaciones')
                ->where('observaciones', 'like', '%' . $anterior . '%')
                ->update([
                    'observaciones' => DB::raw('REPLACE(observaciones, ' . DB::getPdo()->quote($anterior) . ', ' . DB::getPdo()->quote($nuevo) . ')'),
                ]);
        }
    }
}
