<?php

namespace App\Http\Controllers;

use App\Models\LotePago;
use App\Models\Movimiento;
use App\Models\PagoCamion;
use App\Models\PagoProveedor;
use Illuminate\Http\Request;
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

        Alert::success('Éxito', "Código actualizado en todos los registros del lote.");
        return back();
    }
}
