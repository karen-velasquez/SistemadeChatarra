<?php

namespace App\Http\Controllers;

use App\Models\CuentaEmpresa;
use App\Models\Empresa;
use App\Models\LoteEntrega;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LoteEntregaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $proveedorFiltro = $request->get('proveedor_id');
        $estadoFiltro    = $request->get('estado', 'Abierto');

        // Paginar por proveedores (10 por página), luego cargar todos sus lotes
        $queryProveedores = Proveedor::orderBy('nombre');
        if ($proveedorFiltro) {
            $queryProveedores->where('id', $proveedorFiltro);
        }
        $proveedoresPaginados = $queryProveedores->paginate(10)->withQueryString();

        $proveedorIds = $proveedoresPaginados->pluck('id');

        $queryLotes = LoteEntrega::with(['proveedor', 'tramos.contratoCamion.contrato', 'pagosExtras.cuentaOrigen.empresa', 'pagosExtras.cuentaOrigen.banco'])
            ->whereIn('proveedor_id', $proveedorIds)
            ->orderByDesc('anio')
            ->orderByDesc('numero_semana')
            ->orderBy('proveedor_id');

        if ($estadoFiltro !== 'Todos') {
            $queryLotes->where('estado', $estadoFiltro);
        }

        $lotes       = $queryLotes->get();
        $proveedores = Proveedor::orderBy('nombre')->get();
        $empresas    = Empresa::with(['cuentas' => function($q) { $q->where('activo', true); }])->get();

        return view('lotes_entrega.index', compact('lotes', 'proveedores', 'proveedoresPaginados', 'estadoFiltro', 'proveedorFiltro', 'empresas'));
    }

    /**
     * Devuelve JSON con los lotes abiertos de un proveedor para el modal de llegada.
     */
    public function lotesProveedor(int $proveedorId)
    {
        $lotes = LoteEntrega::lotesAbiertosParaProveedor($proveedorId);

        return response()->json($lotes->map(fn($l) => [
            'id'           => $l->id,
            'nombre'       => $l->nombre,
            'numero_semana'=> $l->numero_semana,
            'anio'         => $l->anio,
            'fecha_inicio' => $l->fecha_inicio->format('d/m/Y'),
            'fecha_fin'    => $l->fecha_fin->format('d/m/Y'),
        ]));
    }

    public function cerrar(Request $request, string $uuid)
    {
        $lote = LoteEntrega::where('uuid', $uuid)->firstOrFail();

        if ($lote->estado === 'Cerrado') {
            Alert::error('Error', 'El lote ya está cerrado.');
            return back();
        }

        $lote->update([
            'estado'     => 'Cerrado',
            'cerrado_at' => now(),
            'cerrado_by' => auth()->id(),
        ]);

        Alert::success('Lote cerrado', "El lote {$lote->nombre} fue cerrado correctamente.");
        return back();
    }
}
