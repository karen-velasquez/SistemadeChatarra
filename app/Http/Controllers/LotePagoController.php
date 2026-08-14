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

    private function modeloDelLote(LotePago $lote): string
    {
        return match ($lote->tipo) {
            'proveedor' => PagoProveedor::class,
            'cliente'   => PagoCliente::class,
            default     => PagoCamion::class,
        };
    }
}
