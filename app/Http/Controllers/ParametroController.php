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

    // Quita espacios de los extremos y colapsa espacios múltiples internos a uno solo
    // (" VOLVO   TRUCK  " -> "VOLVO TRUCK"), sin alterar el resto del texto.
    private function normalizarValor(string $valor): string
    {
        return preg_replace('/\s+/', ' ', trim($valor));
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

        $tipo  = strtolower($this->normalizarValor($request->tipo));
        $valor = strtoupper($this->normalizarValor($request->valor));

        $duplicado = Parametro::where('tipo', $tipo)->whereRaw('UPPER(valor) = ?', [$valor])->exists();
        if ($duplicado) {
            Alert::warning('Ya existe', "El valor \"{$valor}\" ya está registrado en este grupo de parámetros.");
            return redirect()->route('parametros.index');
        }

        Parametro::create([
            'tipo'        => $tipo,
            'valor'       => $valor,
            'descripcion' => $request->descripcion ? trim($request->descripcion) : null,
            'created_by'  => auth()->id(),
            'updated_by'  => auth()->id(),
        ]);

        Alert::success('Guardado', 'Parámetro registrado correctamente.');
        return redirect()->route('parametros.index');
    }

    // Crea un parámetro desde un modal rápido (ej: Nuevo Camión) y devuelve JSON
    // en vez de redirigir, para poder inyectar la nueva opción en el <select> sin recargar.
    public function storeAjax(Request $request)
    {
        $request->validate([
            'tipo'  => 'required|string|max:100',
            'valor' => 'required|string|max:255',
        ]);

        $tipo  = strtolower($this->normalizarValor($request->tipo));
        $valor = strtoupper($this->normalizarValor($request->valor));

        $existente = Parametro::where('tipo', $tipo)->whereRaw('UPPER(valor) = ?', [$valor])->first();
        if ($existente) {
            return response()->json([
                'ok'        => false,
                'message'   => "\"{$valor}\" ya está registrado. Selecciónelo de la lista en vez de crearlo de nuevo.",
                'existente' => ['id' => $existente->id, 'valor' => $existente->valor],
            ], 409);
        }

        $parametro = Parametro::create([
            'tipo'       => $tipo,
            'valor'      => $valor,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'ok'   => true,
            'item' => ['id' => $parametro->id, 'valor' => $parametro->valor],
        ]);
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
            'tipo'        => strtolower($this->normalizarValor($request->tipo)),
            'valor'       => strtoupper($this->normalizarValor($request->valor)),
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
