<?php

namespace App\Http\Controllers;

use App\Models\LoteEntrega;
use App\Models\Movimiento;
use App\Models\PagoExtraLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class PagoExtraLoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:contratos.edit');
    }

    public function store(Request $request, string $uuid)
    {
        $lote = LoteEntrega::where('uuid', $uuid)
            ->with('proveedor')
            ->firstOrFail();

        $request->validate([
            'cuenta_origen_id'   => 'required|exists:cuentas_empresa,id',
            'monto'              => 'required|numeric|min:0.01',
            'moneda'             => 'required|string|max:10',
            'tipo_cambio'        => 'required|numeric|min:0.0001',
            'fecha'              => 'required|date',
            'metodo_pago'        => 'required|in:transferencia,qr',
            'codigo_seguimiento' => 'nullable|string|max:100',
            'descripcion'        => 'nullable|string|max:500',
        ], [
            'cuenta_origen_id.required' => 'Seleccione la cuenta de origen.',
            'monto.required'            => 'Ingrese el monto del pago.',
            'monto.min'                 => 'El monto debe ser mayor a 0.',
            'metodo_pago.required'      => 'Seleccione el método de pago.',
            'fecha.required'            => 'Ingrese la fecha del pago.',
        ]);

        $montoBs = round((float)$request->monto * (float)$request->tipo_cambio, 2);

        // Auto-generar código si no se ingresó
        $metodo = $request->metodo_pago;
        $prefijo = $metodo === 'transferencia' ? 'TR' : 'QR';
        $codigoSeguimiento = $request->codigo_seguimiento
            ?: $prefijo . '-' . $lote->codigo . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        DB::transaction(function () use ($request, $lote, $montoBs, $metodo, $codigoSeguimiento) {
            $pago = PagoExtraLote::create([
                'lote_entrega_id'    => $lote->id,
                'cuenta_origen_id'   => $request->cuenta_origen_id,
                'monto'              => $request->monto,
                'moneda'             => $request->moneda,
                'tipo_cambio'        => $request->tipo_cambio,
                'monto_bolivianos'   => $montoBs,
                'fecha'              => $request->fecha,
                'metodo_pago'        => $metodo,
                'codigo_seguimiento' => $codigoSeguimiento,
                'descripcion'        => $request->descripcion,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            $concepto = 'Pago extra lote ' . ($lote->codigo ?? "#{$lote->id}")
                . ' — ' . $lote->proveedor->nombre
                . ' (Sem. ' . $lote->numero_semana . '/' . $lote->anio
                . ', ' . $lote->fecha_inicio->format('d/m') . ' al ' . $lote->fecha_fin->format('d/m/Y') . ')'
                . ($request->descripcion ? ': ' . $request->descripcion : '');

            Movimiento::registrarDePago($pago, 'egreso', 'pago_proveedor', $request->cuenta_origen_id, $concepto, $request->descripcion);
        });

        Alert::success('Pago registrado', 'El pago extra fue registrado y reflejado en movimientos.');
        return back();
    }

    public function destroy(string $uuid)
    {
        $pago = PagoExtraLote::where('uuid', $uuid)->firstOrFail();

        // Eliminar movimiento asociado. Debe borrarse modelo por modelo (each) para
        // disparar los eventos de booted() que revierten el saldo; un delete() masivo no los dispara.
        DB::transaction(function () use ($pago) {
            $pago->movimiento()->each(fn($m) => $m->delete());
            $pago->delete();
        });

        Alert::success('Eliminado', 'El pago extra fue eliminado y el movimiento revertido.');
        return back();
    }
}
