<?php

namespace App\Http\Controllers;

use App\Models\Parametro;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ParametroController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $parametros = Parametro::orderBy('tipo')->orderBy('descripcion')->get()->groupBy('tipo');
        $tipos = Parametro::select('tipo')->distinct()->orderBy('tipo')->pluck('tipo');
        $idempotencyToken = $this->generarToken('parametro_store_token');
        return view('parametros.index', compact('parametros', 'tipos', 'idempotencyToken'));
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('parametro_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('parametros.index');
        }
        $request->validate([
            'tipo'        => 'required|string|max:100',
            'valor'       => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Parametro::create([
            'tipo'        => strtolower(trim($request->tipo)),
            'valor'       => strtoupper(trim($request->valor)),
            'descripcion' => $request->descripcion ? trim($request->descripcion) : null,
            'created_by'  => auth()->id(),
            'updated_by'  => auth()->id(),
        ]);

        Alert::success('Guardado', 'Parámetro registrado correctamente.');
        return redirect()->route('parametros.index');
    }

    public function update(Request $request, string $uuid)
    {
        $parametro = Parametro::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'tipo'        => 'required|string|max:100',
            'valor'       => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $parametro->update([
            'tipo'        => strtolower(trim($request->tipo)),
            'valor'       => strtoupper(trim($request->valor)),
            'descripcion' => $request->descripcion ? trim($request->descripcion) : null,
            'updated_by'  => auth()->id(),
        ]);

        Alert::success('Actualizado', 'Parámetro actualizado.');
        return redirect()->route('parametros.index');
    }

    public function destroy(string $uuid)
    {
        $parametro = Parametro::where('uuid', $uuid)->firstOrFail();

        // Verificar si el parámetro está siendo utilizado
        $verificacion = $parametro->verificarUso();

        if ($verificacion['enUso']) {
            Alert::warning('No se puede eliminar', $verificacion['mensaje']);
            return redirect()->route('parametros.index');
        }

        $parametro->delete();
        Alert::success('Eliminado', 'Parámetro eliminado correctamente.');
        return redirect()->route('parametros.index');
    }

    public function edit(string $uuid)
    {
        $parametro = Parametro::where('uuid', $uuid)->firstOrFail();
        return response()->json($parametro);
    }
}
