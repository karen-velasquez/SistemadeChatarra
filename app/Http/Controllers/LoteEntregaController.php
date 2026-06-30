<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\LoteEntrega;
use App\Models\Proveedor;
use Carbon\Carbon;
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

        $query = LoteEntrega::with(['proveedor', 'tramos.contratoCamion.contrato', 'pagosExtras.cuentaOrigen.empresa', 'pagosExtras.cuentaOrigen.banco'])
            ->orderByDesc('anio')
            ->orderByDesc('numero_semana')
            ->orderBy('proveedor_id');

        if ($proveedorFiltro) {
            $query->where('proveedor_id', $proveedorFiltro);
        }
        if ($estadoFiltro !== 'Todos') {
            $query->where('estado', $estadoFiltro);
        }

        $lotes       = $query->paginate(100)->withQueryString();
        $proveedores = Proveedor::orderBy('nombre')->get();
        $empresas    = Empresa::with(['cuentas' => fn($q) => $q->where('activo', true)])->get();

        return view('lotes_entrega.index', compact('lotes', 'proveedores', 'estadoFiltro', 'proveedorFiltro', 'empresas'));
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

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'observaciones'=> 'nullable|string|max:500',
        ]);

        $proveedor = Proveedor::findOrFail($request->proveedor_id);

        if ($proveedor->tipo_proveedor !== 'INTERNACIONAL') {
            Alert::error('No permitido', 'Solo se pueden crear lotes manuales para proveedores INTERNACIONALES.');
            return back();
        }

        $inicio = Carbon::parse($request->fecha_inicio);
        $fin    = Carbon::parse($request->fecha_fin);

        // Verificar solapamiento con lotes abiertos existentes del mismo proveedor
        $solapado = LoteEntrega::where('proveedor_id', $proveedor->id)
            ->where('estado', 'Abierto')
            ->where('fecha_inicio', '<=', $fin->toDateString())
            ->where('fecha_fin',    '>=', $inicio->toDateString())
            ->first();

        if ($solapado) {
            Alert::error(
                'Rango de fechas solapado',
                "Ya existe el lote \"{$solapado->codigo}\" para \"{$proveedor->nombre}\" del {$solapado->fecha_inicio->format('d/m/Y')} al {$solapado->fecha_fin->format('d/m/Y')} que se solapa con el rango ingresado."
            );
            return back()->withInput();
        }

        LoteEntrega::create([
            'proveedor_id'  => $proveedor->id,
            'numero_semana' => (int) $inicio->format('W'),
            'anio'          => (int) $inicio->format('o'),
            'fecha_inicio'  => $inicio->toDateString(),
            'fecha_fin'     => $fin->toDateString(),
            'estado'        => 'Abierto',
            'observaciones' => $request->observaciones,
            'created_by'    => auth()->id(),
        ]);

        Alert::success('Lote creado', "Lote registrado para {$proveedor->nombre}.");
        return redirect()->route('lotes_entrega.index', ['proveedor_id' => $proveedor->id]);
    }

    public function storeAjax(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedors,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'observaciones'=> 'nullable|string|max:500',
        ]);

        $proveedor = Proveedor::findOrFail($request->proveedor_id);

        if ($proveedor->tipo_proveedor !== 'INTERNACIONAL') {
            return response()->json(['error' => 'Solo se pueden crear lotes manuales para proveedores INTERNACIONALES.'], 422);
        }

        $inicio = Carbon::parse($request->fecha_inicio);
        $fin    = Carbon::parse($request->fecha_fin);

        $solapado = LoteEntrega::where('proveedor_id', $proveedor->id)
            ->where('estado', 'Abierto')
            ->where('fecha_inicio', '<=', $fin->toDateString())
            ->where('fecha_fin',    '>=', $inicio->toDateString())
            ->first();

        if ($solapado) {
            return response()->json([
                'error' => "Ya existe el lote \"{$solapado->codigo}\" del {$solapado->fecha_inicio->format('d/m/Y')} al {$solapado->fecha_fin->format('d/m/Y')} que se solapa con el rango ingresado.",
            ], 422);
        }

        $lote = LoteEntrega::create([
            'proveedor_id'  => $proveedor->id,
            'numero_semana' => (int) $inicio->format('W'),
            'anio'          => (int) $inicio->format('o'),
            'fecha_inicio'  => $inicio->toDateString(),
            'fecha_fin'     => $fin->toDateString(),
            'estado'        => 'Abierto',
            'observaciones' => $request->observaciones,
            'created_by'    => auth()->id(),
        ]);

        return response()->json([
            'id'     => $lote->id,
            'nombre' => $lote->nombre,
        ]);
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
