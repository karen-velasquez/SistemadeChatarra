<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\CamionDocumento;
use App\Models\CamionMantenimiento;
use App\Models\CamionPlanMantenimiento;
use App\Models\Taller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class UnidadPropiaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $unidades = Camion::with(['marca', 'tipoVehiculo', 'conductorActual.conductor', 'documentos', 'planMantenimientos'])
            ->where('es_propio', true)
            ->whereNull('deleted_at')
            ->orderBy('placa')
            ->get();

        $disponibles = Camion::where('es_propio', false)
            ->whereNull('deleted_at')
            ->orderBy('placa')
            ->get();

        $talleres = Taller::whereNull('deleted_at')->orderBy('nombre')->get();

        return view('unidades.index', compact('unidades', 'disponibles', 'talleres'));
    }

    public function show($uuid)
    {
        $camion = Camion::with([
                'marca', 'tipoVehiculo', 'placaPais', 'fotos', 'conductorActual.conductor',
                'documentos' => fn($q) => $q->orderByDesc('created_at'),
                'mantenimientos' => fn($q) => $q->with('taller')->orderByDesc('fecha'),
                'planMantenimientos',
            ])
            ->where('uuid', $uuid)
            ->where('es_propio', true)
            ->firstOrFail();

        $talleres = Taller::whereNull('deleted_at')->orderBy('nombre')->get();

        return view('unidades.show', compact('camion', 'talleres'));
    }

    // Marcar un camión existente como unidad propia
    public function marcar(Request $request)
    {
        $request->validate(['camion_id' => 'required|exists:camiones,id']);
        Camion::findOrFail($request->camion_id)->update(['es_propio' => true]);
        Alert::success('Unidades Propias', 'Camión agregado como unidad propia.');
        return redirect()->route('unidades.index');
    }

    public function desmarcar($uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        $camion->update(['es_propio' => false]);
        Alert::success('Unidades Propias', 'El camión ya no figura como unidad propia.');
        return redirect()->route('unidades.index');
    }

    public function actualizarKm(Request $request, $uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        $request->validate(['kilometraje_actual' => 'required|integer|min:' . $camion->kilometraje_actual]);
        $camion->update(['kilometraje_actual' => $request->kilometraje_actual]);
        Alert::success('Kilometraje', 'Kilometraje actualizado.');
        return back();
    }

    // ===== Documentos =====
    public function storeDocumento(Request $request, $uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'tipo'              => 'required|in:RUAT,CONTRATO,IMPUESTO,SEGURO,SOAT,OTRO',
            'descripcion'       => 'nullable|string|max:255',
            'archivo'           => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'fecha_emision'     => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_emision',
            'monto'             => 'nullable|numeric|min:0',
            'observaciones'     => 'nullable|string',
        ]);

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('camiones/documentos', 'public');
        }
        $data['camion_id'] = $camion->id;
        CamionDocumento::create($data);

        Alert::success('Documentos', 'Documento registrado con éxito.');
        return back();
    }

    public function verDocumento($uuid)
    {
        $doc = CamionDocumento::where('uuid', $uuid)->firstOrFail();
        abort_if(!$doc->archivo, 404, 'Este documento no tiene archivo cargado.');
        $path = Storage::disk('public')->path($doc->archivo);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        return response()->file($path);
    }

    public function destroyDocumento($uuid)
    {
        $doc = CamionDocumento::where('uuid', $uuid)->firstOrFail();
        if ($doc->archivo) {
            Storage::disk('public')->delete($doc->archivo);
        }
        $doc->delete();
        Alert::success('Documentos', 'Documento eliminado.');
        return back();
    }

    // ===== Mantenimientos =====
    public function storeMantenimiento(Request $request, $uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'taller_id'   => 'nullable|exists:talleres,id',
            'fecha'       => 'required|date',
            'tipo'        => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'costo'       => 'required|numeric|min:0',
            'kilometraje' => 'nullable|integer|min:0',
            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $request->file('comprobante')->store('camiones/mantenimientos', 'public');
        }
        $data['camion_id'] = $camion->id;
        CamionMantenimiento::create($data);

        // Si el km del mantenimiento supera el actual del camión, actualizarlo
        if (!empty($data['kilometraje']) && $data['kilometraje'] > $camion->kilometraje_actual) {
            $camion->update(['kilometraje_actual' => $data['kilometraje']]);
        }

        Alert::success('Mantenimiento', 'Mantenimiento registrado con éxito.');
        return back();
    }

    public function verComprobante($uuid)
    {
        $mant = CamionMantenimiento::where('uuid', $uuid)->firstOrFail();
        abort_if(!$mant->comprobante, 404, 'Este mantenimiento no tiene comprobante.');
        $path = Storage::disk('public')->path($mant->comprobante);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        return response()->file($path);
    }

    public function destroyMantenimiento($uuid)
    {
        $mant = CamionMantenimiento::where('uuid', $uuid)->firstOrFail();
        if ($mant->comprobante) {
            Storage::disk('public')->delete($mant->comprobante);
        }
        $mant->delete();
        Alert::success('Mantenimiento', 'Registro de mantenimiento eliminado.');
        return back();
    }

    // ===== Plan de mantenimiento por kilometraje =====
    public function storePlan(Request $request, $uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        $data = $request->validate([
            'tarea'        => 'required|string|max:150',
            'intervalo_km' => 'required|integer|min:1',
            'ultimo_km'    => 'nullable|integer|min:0',
            'notas'        => 'nullable|string',
        ]);
        $data['camion_id'] = $camion->id;
        $data['ultimo_km'] = $data['ultimo_km'] ?? $camion->kilometraje_actual;
        CamionPlanMantenimiento::create($data);

        Alert::success('Plan de Mantenimiento', 'Control agregado al plan.');
        return back();
    }

    // Marcar la tarea como realizada al kilometraje actual del camión
    public function realizarPlan($uuid)
    {
        $plan = CamionPlanMantenimiento::with('camion')->where('uuid', $uuid)->firstOrFail();
        $plan->update(['ultimo_km' => $plan->camion->kilometraje_actual]);
        Alert::success('Plan de Mantenimiento', 'Tarea marcada como realizada al km actual.');
        return back();
    }

    public function destroyPlan($uuid)
    {
        CamionPlanMantenimiento::where('uuid', $uuid)->firstOrFail()->delete();
        Alert::success('Plan de Mantenimiento', 'Control eliminado del plan.');
        return back();
    }

    // ===== Talleres =====
    public function storeTaller(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:150']);
        Taller::create($request->only(['nombre', 'especialidad', 'telefono', 'direccion', 'contacto', 'observaciones']));
        Alert::success('Talleres', 'Taller registrado con éxito.');
        return back();
    }

    public function updateTaller(Request $request, $uuid)
    {
        $request->validate(['nombre' => 'required|string|max:150']);
        $taller = Taller::where('uuid', $uuid)->firstOrFail();
        $taller->update($request->only(['nombre', 'especialidad', 'telefono', 'direccion', 'contacto', 'observaciones']));
        Alert::success('Talleres', 'Taller actualizado con éxito.');
        return back();
    }

    public function destroyTaller($uuid)
    {
        Taller::where('uuid', $uuid)->firstOrFail()->delete();
        Alert::success('Talleres', 'Taller eliminado.');
        return back();
    }
}
