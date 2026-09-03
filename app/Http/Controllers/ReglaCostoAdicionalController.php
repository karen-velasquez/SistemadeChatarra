<?php

namespace App\Http\Controllers;

use App\Models\ReglaCostoAdicional;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ReglaCostoAdicionalController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $reglas = ReglaCostoAdicional::with(['cliente', 'empresaFacturadora'])
            ->orderByDesc('created_at')
            ->get();

        $clientes = Cliente::whereNull('deleted_at')->orderBy('nombre')->get();
        $empresas = Empresa::whereNull('deleted_at')->orderBy('nombre')->get();

        $idempotencyToken = $this->generarToken('regla_costo_adicional_store_token');

        return view('reglas_costo_adicional.index', compact('reglas', 'clientes', 'empresas', 'idempotencyToken'));
    }

    /**
     * Valida y arma los datos de la regla. Devuelve null si algo no pasó
     * (ya dejó el Alert correspondiente y hay que redirigir sin guardar).
     */
    private function datosValidos(Request $request, ?int $exceptoId = null): ?array
    {
        $request->validate([
            'cliente_id'              => 'nullable|exists:clientes,id',
            'empresa_facturadora_id'  => 'nullable|exists:empresas,id',
            'monto_por_tramo'         => 'required|numeric|min:0.01',
        ]);

        if (!$request->cliente_id && !$request->empresa_facturadora_id) {
            Alert::warning('Falta información', 'Debe seleccionar al menos un cliente o una empresa facturadora.');
            return null;
        }

        $duplicado = ReglaCostoAdicional::where('cliente_id', $request->cliente_id)
            ->where('empresa_facturadora_id', $request->empresa_facturadora_id)
            ->when($exceptoId, fn($q) => $q->where('id', '!=', $exceptoId))
            ->exists();

        if ($duplicado) {
            Alert::warning('Ya existe', 'Ya existe una regla con esa combinación de cliente y empresa facturadora.');
            return null;
        }

        return [
            'cliente_id'             => $request->cliente_id ?: null,
            'empresa_facturadora_id' => $request->empresa_facturadora_id ?: null,
            'monto_por_tramo'        => $request->monto_por_tramo,
        ];
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('regla_costo_adicional_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('reglas_costo_adicional.index');
        }

        $datos = $this->datosValidos($request);
        if (!$datos) {
            return redirect()->route('reglas_costo_adicional.index');
        }

        ReglaCostoAdicional::create($datos + [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Alert::success('Guardado', 'Regla de costo adicional registrada correctamente.');
        return redirect()->route('reglas_costo_adicional.index');
    }

    public function update(Request $request, string $uuid)
    {
        $regla = ReglaCostoAdicional::where('uuid', $uuid)->firstOrFail();

        $datos = $this->datosValidos($request, $regla->id);
        if (!$datos) {
            return redirect()->route('reglas_costo_adicional.index');
        }

        $regla->update($datos + ['updated_by' => auth()->id()]);

        Alert::success('Actualizado', 'Regla de costo adicional actualizada.');
        return redirect()->route('reglas_costo_adicional.index');
    }

    public function toggleActivo(string $uuid)
    {
        $regla = ReglaCostoAdicional::where('uuid', $uuid)->firstOrFail();
        $regla->update(['activo' => !$regla->activo, 'updated_by' => auth()->id()]);

        Alert::success('Actualizado', $regla->activo ? 'Regla activada.' : 'Regla desactivada.');
        return redirect()->route('reglas_costo_adicional.index');
    }

    public function destroy(string $uuid)
    {
        $regla = ReglaCostoAdicional::where('uuid', $uuid)->firstOrFail();
        $regla->delete();

        Alert::success('Eliminado', 'Regla de costo adicional eliminada correctamente.');
        return redirect()->route('reglas_costo_adicional.index');
    }

    public function edit(string $uuid)
    {
        $regla = ReglaCostoAdicional::where('uuid', $uuid)->firstOrFail();
        return response()->json($regla);
    }
}
