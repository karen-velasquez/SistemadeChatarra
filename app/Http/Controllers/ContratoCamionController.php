<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\ContratoCamion;
use App\Models\Tramo;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ContratoCamionController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('contrato_camion_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->back();
        }

        $request->validate([
            'contrato_id'      => 'required|exists:contratos,id',
            'camion_id'        => 'required|exists:camiones,id',
            'conductor_id'     => 'required|exists:operadores_transporte,id',
            'fecha_asignacion' => 'required|date',
            'tipo_tramo'       => 'required|in:Internacional,Nacional',
            'origen'           => 'required|string|max:150',
            'destino'          => 'required|string|max:150',
            'peso_declarado'   => 'required|numeric|min:0.001',
            'monto_acordado'   => 'nullable|numeric|min:0',
            'moneda_flete'     => 'nullable|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'observaciones'    => 'nullable|string|max:500',
        ], [
            'camion_id.required'        => 'Debe seleccionar un camión.',
            'conductor_id.required'     => 'Debe seleccionar un conductor.',
            'fecha_asignacion.required' => 'La fecha de salida es obligatoria.',
            'tipo_tramo.required'     => 'El tipo de tramo es obligatorio.',
            'origen.required'         => 'El origen es obligatorio.',
            'destino.required'        => 'El destino es obligatorio.',
            'peso_declarado.required' => 'El peso declarado por el proveedor es obligatorio.',
        ]);

        $contrato = Contrato::findOrFail($request->contrato_id);

        $cc = ContratoCamion::create([
            'contrato_id'      => $request->contrato_id,
            'camion_id'        => $request->camion_id,
            'conductor_id'     => $request->conductor_id,
            'toneladas'        => $request->peso_declarado,
            'monto_acordado'   => $request->monto_acordado ?: null,
            'moneda_flete'     => $request->moneda_flete ?? 'BOB',
            'fecha_asignacion' => $request->fecha_asignacion,
            'estado_entrega'   => 'Pendiente',
            'observaciones'    => $request->observaciones,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        // Crear el tramo raíz automáticamente
        Tramo::create([
            'contrato_camion_id' => $cc->id,
            'tramo_padre_id'     => null,
            'camion_id'          => $request->camion_id,
            'conductor_id'       => $request->conductor_id,
            'origen'             => $request->origen,
            'destino'            => $request->destino,
            'tipo_tramo'         => $request->tipo_tramo,
            'peso_declarado'     => $request->peso_declarado,
            'peso_salida'        => $request->peso_declarado,
            'fecha_salida'       => $request->fecha_asignacion,
            'estado'             => 'En ruta',
            'observaciones'      => $request->observaciones,
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);

        Alert::success('Éxito', 'Camión asignado al contrato con tramo inicial registrado.');
        return redirect()->route('contratos.camiones', $contrato->uuid);
    }

    public function actualizarFlete(Request $request, $uuid)
    {
        $cc = ContratoCamion::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'moneda_flete'   => 'required|in:BOB,USD,EUR,BRL,ARS,PEN,CLP,PYG,COP',
            'monto_acordado' => 'required|numeric|min:0.01',
        ], [
            'moneda_flete.required'   => 'La moneda es obligatoria.',
            'monto_acordado.required' => 'El monto del flete es obligatorio.',
            'monto_acordado.min'      => 'El monto debe ser mayor a cero.',
        ]);

        $cc->update([
            'moneda_flete'   => $request->moneda_flete,
            'monto_acordado' => $request->monto_acordado,
            'updated_by'     => auth()->id(),
        ]);

        Alert::success('Éxito', 'Flete registrado correctamente.');
        return back();
    }

    public function toggleActivo($uuid)
    {
        $item = ContratoCamion::where('uuid', $uuid)->firstOrFail();
        $contratoUuid = $item->contrato->uuid;

        $nuevoActivo = !$item->activo;

        // Al desactivar: marcar todos los tramos no finales como Desactivado
        // Al reactivar: devolver tramos a En ruta
        if (!$nuevoActivo) {
            $item->tramos()
                ->whereNotIn('estado', ['Entregado', 'Transbordado'])
                ->update(['activo' => false, 'estado' => 'Desactivado', 'updated_by' => auth()->id()]);
        } else {
            $item->tramos()
                ->where('estado', 'Desactivado')
                ->update(['activo' => true, 'estado' => 'En ruta', 'updated_by' => auth()->id()]);
        }

        $item->update([
            'activo'         => $nuevoActivo,
            'estado_entrega' => $nuevoActivo ? 'Pendiente' : 'Desactivado',
            'updated_by'     => auth()->id(),
        ]);

        $msg = $nuevoActivo ? 'Asignación reactivada.' : 'Asignación desactivada. El registro se conserva en el historial.';
        Alert::success('Listo', $msg);
        return redirect()->route('contratos.camiones', $contratoUuid);
    }
}
