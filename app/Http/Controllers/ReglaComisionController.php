<?php

namespace App\Http\Controllers;

use App\Models\ReglaComision;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ReglaComisionController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $reglas = ReglaComision::with(['cliente', 'empresaFacturadora'])
            ->orderByDesc('created_at')
            ->get();

        $clientes = Cliente::whereNull('deleted_at')->orderBy('nombre')->get();
        $empresas = Empresa::whereNull('deleted_at')->orderBy('nombre')->get();

        $idempotencyToken = $this->generarToken('regla_comision_store_token');

        return view('reglas_comision.index', compact('reglas', 'clientes', 'empresas', 'idempotencyToken'));
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
            'monto_por_tonelada'      => 'required|numeric|min:0',
            'mes'                     => 'nullable|integer|min:1|max:12',
            'anio'                    => 'required_with:mes|nullable|integer|min:2000|max:2100',
        ]);

        if (!$request->cliente_id && !$request->empresa_facturadora_id) {
            Alert::warning('Falta información', 'Debe seleccionar al menos un cliente o una empresa facturadora.');
            return null;
        }

        $vigenteDesde = $request->filled('mes')
            ? \Carbon\Carbon::create($request->anio, $request->mes, 1)->toDateString()
            : null;

        $duplicado = ReglaComision::where('cliente_id', $request->cliente_id)
            ->where('empresa_facturadora_id', $request->empresa_facturadora_id)
            ->where('vigente_desde', $vigenteDesde)
            ->when($exceptoId, fn($q) => $q->where('id', '!=', $exceptoId))
            ->exists();

        if ($duplicado) {
            Alert::warning('Ya existe', 'Ya existe una regla con esa combinación de cliente, empresa facturadora y mes de vigencia.');
            return null;
        }

        return [
            'cliente_id'             => $request->cliente_id ?: null,
            'empresa_facturadora_id' => $request->empresa_facturadora_id ?: null,
            'monto_por_tonelada'     => $request->monto_por_tonelada,
            'vigente_desde'          => $vigenteDesde,
        ];
    }

    public function store(Request $request)
    {
        if (!$this->tokenValido('regla_comision_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('reglas_comision.index');
        }

        $datos = $this->datosValidos($request);
        if (!$datos) {
            return redirect()->route('reglas_comision.index');
        }

        ReglaComision::create($datos + [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Alert::success('Guardado', 'Regla de comisión registrada correctamente.');
        return redirect()->route('reglas_comision.index');
    }

    public function update(Request $request, string $uuid)
    {
        $regla = ReglaComision::where('uuid', $uuid)->firstOrFail();

        $datos = $this->datosValidos($request, $regla->id);
        if (!$datos) {
            return redirect()->route('reglas_comision.index');
        }

        $regla->update($datos + ['updated_by' => auth()->id()]);

        Alert::success('Actualizado', 'Regla de comisión actualizada.');
        return redirect()->route('reglas_comision.index');
    }

    public function toggleActivo(string $uuid)
    {
        $regla = ReglaComision::where('uuid', $uuid)->firstOrFail();
        $regla->update(['activo' => !$regla->activo, 'updated_by' => auth()->id()]);

        Alert::success('Actualizado', $regla->activo ? 'Regla activada.' : 'Regla desactivada.');
        return redirect()->route('reglas_comision.index');
    }

    public function destroy(string $uuid)
    {
        $regla = ReglaComision::where('uuid', $uuid)->firstOrFail();
        $regla->delete();

        Alert::success('Eliminado', 'Regla de comisión eliminada correctamente.');
        return redirect()->route('reglas_comision.index');
    }

    public function edit(string $uuid)
    {
        $regla = ReglaComision::where('uuid', $uuid)->firstOrFail();
        return response()->json($regla);
    }
}
