<?php

namespace App\Http\Controllers;

use App\Models\OperadorTransporte;
use App\Http\Requests\OperadorTransporteRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class OperadorTransporteController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $operadores = OperadorTransporte::whereNull('deleted_at')->get();
        return view('camiones.index', compact('operadores'));
    }

    public function store(OperadorTransporteRequest $request)
    {
        if (!$this->tokenValido('operador_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('camiones.index', ['tab' => 'operadores']);
        }

        $data = $request->except(['doc_carnet', 'doc_licencia']);

        if ($request->hasFile('doc_carnet')) {
            $data['doc_carnet'] = $request->file('doc_carnet')->store('operadores/carnets', 'public');
        }
        if ($request->hasFile('doc_licencia')) {
            $data['doc_licencia'] = $request->file('doc_licencia')->store('operadores/licencias', 'public');
        }

        OperadorTransporte::create($data);
        Alert::success('Registro', 'Operador registrado con éxito.');
        return redirect()->route('camiones.index', ['tab' => 'operadores']);
    }

    public function edit($uuid)
    {
        $operador = OperadorTransporte::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'id'                   => $operador->id,
            'nombre'               => $operador->nombre,
            'apellido_paterno'     => $operador->apellido_paterno,
            'apellido_materno'     => $operador->apellido_materno,
            'ci'                   => $operador->ci,
            'ci_pais_id'           => $operador->ci_pais_id,
            'telefono'             => $operador->telefono,
            'email'                => $operador->email,
            'direccion'            => $operador->direccion,
            'tipo_operador'        => $operador->tipo_operador,
            'estado'               => $operador->estado,
            'licencia_numero'      => $operador->licencia_numero,
            'licencia_pais_id'     => $operador->licencia_pais_id,
            'licencia_vencimiento' => $operador->licencia_vencimiento?->format('Y-m-d'),
            'doc_carnet'           => $operador->doc_carnet,
            'doc_licencia'         => $operador->doc_licencia,
        ]);
    }

    public function update(OperadorTransporteRequest $request, OperadorTransporte $operador)
    {
        $data = $request->except(['doc_carnet', 'doc_licencia']);

        if ($request->hasFile('doc_carnet')) {
            if ($operador->doc_carnet) {
                Storage::disk('public')->delete($operador->doc_carnet);
            }
            $data['doc_carnet'] = $request->file('doc_carnet')->store('operadores/carnets', 'public');
        }
        if ($request->hasFile('doc_licencia')) {
            if ($operador->doc_licencia) {
                Storage::disk('public')->delete($operador->doc_licencia);
            }
            $data['doc_licencia'] = $request->file('doc_licencia')->store('operadores/licencias', 'public');
        }

        $operador->update($data);
        Alert::success('Actualización', 'Operador actualizado con éxito.');
        return redirect()->route('camiones.index', ['tab' => 'operadores']);
    }

    public function destroy($uuid)
    {
        $operador = OperadorTransporte::where('uuid', $uuid)->firstOrFail();
        $operador->delete();
        Alert::success('Eliminación', 'Operador eliminado con éxito.');
        return redirect()->route('camiones.index', ['tab' => 'operadores']);
    }

    public function verCarnet($uuid)
    {
        $operador = OperadorTransporte::where('uuid', $uuid)->firstOrFail();
        abort_if(!$operador->doc_carnet, 404, 'Este operador no tiene carnet cargado.');
        $path = Storage::disk('public')->path($operador->doc_carnet);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        $mime = mime_content_type($path);
        return response()->file($path, ['Content-Type' => $mime]);
    }

    public function verLicencia($uuid)
    {
        $operador = OperadorTransporte::where('uuid', $uuid)->firstOrFail();
        abort_if(!$operador->doc_licencia, 404, 'Este operador no tiene licencia cargada.');
        $path = Storage::disk('public')->path($operador->doc_licencia);
        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');
        $mime = mime_content_type($path);
        return response()->file($path, ['Content-Type' => $mime]);
    }
}
