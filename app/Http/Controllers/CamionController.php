<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\CamionConductor;
use App\Models\CamionFoto;
use App\Models\OperadorTransporte;
use App\Models\Parametro;
use App\Http\Requests\CamionRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class CamionController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $camiones   = Camion::with(['propietario', 'fotos', 'marca', 'tipoVehiculo', 'placaPais'])
                            ->whereNull('deleted_at')->get();
        $operadores = OperadorTransporte::with(['ciPais', 'licenciaPais'])->whereNull('deleted_at')->orderBy('nombre')->get();
        $choferes   = OperadorTransporte::with(['ciPais', 'licenciaPais'])
                            ->whereNull('deleted_at')
                            ->whereIn('tipo_operador', ['chofer', 'ambos'])
                            ->whereNotNull('licencia_numero')
                            ->orderBy('nombre')->get();
        $asignaciones = CamionConductor::with(['camion', 'conductor'])
                            ->orderByDesc('fecha_inicio')->get();

        $marcas = Parametro::where('tipo', 'camion_marca')->orderBy('valor')->get();
        $tiposVehiculo = Parametro::where('tipo', 'camion_tipo')->orderBy('valor')->get();
        $paises = Parametro::where('tipo', 'paises')->orderBy('valor')->get();
        $paisesDocumento = Parametro::where('tipo', 'pais_documento')->get();

        $tokenCamion    = $this->generarToken('camion_store_token');
        $tokenOperador  = $this->generarToken('operador_store_token');
        $tokenConductor = $this->generarToken('conductor_store_token');

        return view('camiones.index', compact('camiones', 'operadores', 'choferes', 'asignaciones', 'marcas', 'tiposVehiculo', 'paises', 'paisesDocumento', 'tokenCamion', 'tokenOperador', 'tokenConductor'));
    }

    public function nuevoToken()
    {
        return response()->json([
            'camion_token'    => $this->generarToken('camion_store_token'),
            'operador_token'  => $this->generarToken('operador_store_token'),
            'conductor_token' => $this->generarToken('conductor_store_token'),
        ]);
    }

    public function buscarPorPlaca(\Illuminate\Http\Request $request)
    {
        $placa = strtoupper(trim($request->query('placa', '')));
        if (!$placa) {
            return response()->json(null);
        }
        $camion = Camion::with(['fotos', 'marca', 'tipoVehiculo'])
            ->whereNull('deleted_at')
            ->where('placa', $placa)
            ->first();
        if (!$camion) {
            return response()->json(null);
        }
        return response()->json([
            'id'               => $camion->id,
            'placa'            => $camion->placa,
            'placa_pais_id'    => $camion->placa_pais_id,
            'tipo_vehiculo_id' => $camion->tipo_vehiculo_id,
            'marca_id'         => $camion->marca_id,
            'modelo'           => $camion->modelo,
            'anio'             => $camion->anio,
            'capacidad_kg'     => $camion->capacidad_kg,
            'color'            => $camion->color,
            'estado'           => $camion->estado,
            'propietario_id'   => $camion->propietario_id,
            'documento_ruat'   => $camion->documento_ruat,
            'fotos'            => $camion->fotos,
            'marca_label'      => $camion->marca->valor ?? '-',
            'tipo_label'       => $camion->tipoVehiculo->valor ?? '-',
        ]);
    }

    public function store(CamionRequest $request)
    {
        if (!$this->tokenValido('camion_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('camiones.index');
        }
        $data = $request->except(['documento_ruat', 'fotos', 'capacidad_tn']);
        $data['capacidad_kg'] = $request->capacidad_tn * 1000;

        if ($request->hasFile('documento_ruat')) {
            $data['documento_ruat'] = $request->file('documento_ruat')
                ->store('camiones/ruat', 'public');
        }

        $camion = Camion::create($data);

        if ($request->hasFile('fotos')) {
            $fotos = is_array($request->file('fotos')) ? $request->file('fotos') : [$request->file('fotos')];
            foreach ($fotos as $foto) {
                $ruta = $foto->store('camiones/fotos', 'public');
                CamionFoto::create(['camion_id' => $camion->id, 'ruta' => $ruta]);
            }
        }

        Alert::success('Registro', 'Camión registrado con éxito.');
        return redirect()->route('camiones.index');
    }

    public function edit($uuid)
    {
        $camion = Camion::with('fotos')->where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'id'              => $camion->id,
            'placa'           => $camion->placa,
            'placa_pais_id'   => $camion->placa_pais_id,
            'tipo_vehiculo_id'=> $camion->tipo_vehiculo_id,
            'marca_id'        => $camion->marca_id,
            'modelo'          => $camion->modelo,
            'anio'            => $camion->anio,
            'capacidad_kg'    => $camion->capacidad_kg,
            'color'           => $camion->color,
            'estado'          => $camion->estado,
            'propietario_id'  => $camion->propietario_id,
            'documento_ruat'  => $camion->documento_ruat,
            'fotos'           => $camion->fotos,
        ]);
    }

    public function update(CamionRequest $request, Camion $camion)
    {
        $data = $request->except(['documento_ruat', 'fotos', 'capacidad_tn']);
        $data['capacidad_kg'] = $request->capacidad_tn * 1000;

        if ($request->hasFile('documento_ruat')) {
            if ($camion->documento_ruat) {
                Storage::disk('public')->delete($camion->documento_ruat);
            }
            $data['documento_ruat'] = $request->file('documento_ruat')
                ->store('camiones/ruat', 'public');
        }

        $camion->update($data);

        if ($request->hasFile('fotos')) {
            $totalActual = $camion->fotos()->count();
            $nuevas      = $request->file('fotos');
            $nuevas      = is_array($nuevas) ? $nuevas : [$nuevas];
            $disponibles = max(0, 5 - $totalActual);

            foreach (array_slice($nuevas, 0, $disponibles) as $foto) {
                $ruta = $foto->store('camiones/fotos', 'public');
                CamionFoto::create(['camion_id' => $camion->id, 'ruta' => $ruta]);
            }
        }

        Alert::success('Actualización', 'Datos del camión actualizados con éxito.');
        return redirect()->route('camiones.index');
    }

    public function verRuat($uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        abort_if(!$camion->documento_ruat, 404, 'Este camión no tiene RUAT cargado.');
        $path = Storage::disk('public')->path($camion->documento_ruat);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }

    public function eliminarFoto(CamionFoto $foto)
    {
        Storage::disk('public')->delete($foto->ruta);
        $foto->delete();
        return response()->json(['ok' => true]);
    }

    public function destroy($uuid)
    {
        $camion = Camion::where('uuid', $uuid)->firstOrFail();
        $camion->delete();
        Alert::success('Eliminación', 'Camión eliminado con éxito.');
        return redirect()->route('camiones.index');
    }
}
