<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\CamionConductor;
use App\Models\OperadorTransporte;
use App\Http\Requests\CamionConductorRequest;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class CamionConductorController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(CamionConductorRequest $request)
    {
        if (!$this->tokenValido('conductor_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('camiones.index', ['tab' => 'conductores']);
        }

        CamionConductor::create(array_merge($request->validated(), [
            'fecha_inicio' => now()->toDateString(),
        ]));
        Alert::success('Asignación', 'Conductor asignado al camión con éxito.');
        return redirect()->route('camiones.index', ['tab' => 'conductores']);
    }

    public function finalizarAsignacion($uuid)
    {
        $asignacion = CamionConductor::where('uuid', $uuid)->firstOrFail();
        $asignacion->update(['fecha_fin' => now()->toDateString()]);
        Alert::success('Finalización', 'Asignación finalizada con éxito.');
        return redirect()->route('camiones.index', ['tab' => 'conductores']);
    }

    public function reiniciarAsignacion($uuid)
    {
        $asignacion = CamionConductor::where('uuid', $uuid)->firstOrFail();
        $asignacion->update(['fecha_fin' => null]);
        Alert::success('Reinicio', 'Asignación reactivada con éxito.');
        return redirect()->route('camiones.index', ['tab' => 'conductores']);
    }

    // Endpoint: camiones con propietario y conductor actual
    public function camionesConDetalle()
    {
        $camiones = Camion::with([
            'propietario',
            'conductorActual.conductor',
            'marca',
            'tipoVehiculo',
            'placaPais',
        ])->whereNull('deleted_at')->get()->map(function ($camion) {
            return [
                'uuid'             => $camion->uuid,
                'placa'            => $camion->placa,
                'tipo_vehiculo'    => $camion->tipo_vehiculo,
                'marca'            => $camion->marca,
                'modelo'           => $camion->modelo,
                'anio'             => $camion->anio,
                'capacidad_kg'     => $camion->capacidad_kg,
                'estado'           => $camion->estado,
                'propietario'      => $camion->propietario
                    ? $camion->propietario->nombre_completo
                    : null,
                'conductor_actual' => $camion->conductorActual
                    ? $camion->conductorActual->conductor->nombre_completo
                    : null,
            ];
        });

        return response()->json($camiones);
    }

    // Endpoint: todos los conductores con licencia que NO están asignados activamente a este camión
    public function conductoresDisponibles($uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();

        // IDs ya asignados activamente a este camión
        $asignadosIds = CamionConductor::where('camion_id', $camion->id)
            ->whereNull('fecha_fin')
            ->pluck('conductor_id');

        $conductores = OperadorTransporte::whereIn('tipo_operador', ['chofer', 'ambos'])
            ->whereNotNull('licencia_numero')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $asignadosIds)
            ->orderBy('nombre')
            ->get()
            ->map(fn($c) => [
                'id'       => $c->id,
                'nombre'   => $c->nombre_completo,
                'licencia' => $c->licencia_numero,
            ]);

        return response()->json($conductores);
    }

    // Endpoint: conductores con asignación activa en el camión + propietario si conduce
    public function conductoresRelacionados($uuid)
    {
        $camion = Camion::with([
            'propietario',
            'conductores' => fn($q) => $q->whereNull('fecha_fin'),
            'marca',
            'tipoVehiculo',
            'placaPais',
            'conductores.conductor',
        ])->where('uuid', $uuid)->firstOrFail();

        $conductores = collect();

        // Propietario si puede conducir
        if ($camion->propietario && $camion->propietario->puedeConducir()) {
            $conductores->push([
                'id'       => $camion->propietario->id,
                'nombre'   => $camion->propietario->nombre_completo,
                'licencia' => $camion->propietario->licencia_numero,
                'tipo'     => 'Propietario / Conductor',
            ]);
        }

        // Conductores con asignación activa (fecha_fin NULL) en este camión
        $camion->conductores
            ->pluck('conductor')
            ->unique('id')
            ->each(function ($c) use ($conductores) {
                if (!$conductores->contains('id', $c->id)) {
                    $conductores->push([
                        'id'       => $c->id,
                        'nombre'   => $c->nombre_completo,
                        'licencia' => $c->licencia_numero,
                        'tipo'     => 'Conductor Asignado',
                    ]);
                }
            });

        return response()->json($conductores->values());
    }

    // Endpoint: historial de conductores por camión
    public function historialConductores($uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();

        $historial = $camion->conductores()
            ->with('conductor')
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(function ($asig) {
                return [
                    'uuid'          => $asig->uuid,
                    'conductor'     => $asig->conductor->nombre_completo,
                    'licencia'      => $asig->conductor->licencia_numero,
                    'fecha_inicio'  => $asig->fecha_inicio?->format('d/m/Y'),
                    'fecha_fin'     => $asig->fecha_fin?->format('d/m/Y') ?? 'Activo',
                    'observaciones' => $asig->observaciones,
                ];
            });

        return response()->json([
            'camion'   => $camion->placa . ' - ' . $camion->marca . ' ' . $camion->modelo,
            'historial' => $historial,
        ]);
    }
}
