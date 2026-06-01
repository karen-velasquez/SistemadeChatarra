<?php

namespace App\Http\Controllers;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\GastoExtra;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use App\Exports\ReporteGeneralExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
        $proveedores = Proveedor::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();
        $clientes = Cliente::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();
        $contratosQuery = Contrato::with(['proveedor', 'cliente'])->whereNull('deleted_at');
        if ($request->filled('proveedor_id')) {
            $contratosQuery->where('proveedor_id', $request->proveedor_id);
        }
        if ($request->filled('cliente_id')) {
            $contratosQuery->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('tipo_contrato')) {
            $contratosQuery->where('tipo_contrato', $request->tipo_contrato);
        }
        $contratos = $contratosQuery->get();
        $contratosIds = $contratos->pluck('id');
        $pagosProveedor = PagoProveedor::with(['contrato.proveedor'])->whereNull('deleted_at')->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
        if ($contratosIds->isNotEmpty()) {
            $pagosProveedor->whereIn('contrato_id', $contratosIds);
        }
        $pagosProveedor = $pagosProveedor->get();
        $cobrosCliente = PagoCliente::with(['contrato.cliente'])->whereNull('deleted_at')->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
        $cobrosCliente = $cobrosCliente->get();
        $gastosExtras = GastoExtra::with(['contrato.proveedor'])->whereNull('deleted_at')->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        if ($contratosIds->isNotEmpty()) {
            $gastosExtras->whereIn('contrato_id', $contratosIds);
        }
        $gastosExtras = $gastosExtras->get();
        $pagosCamiones = PagoCamion::with(['contrato.proveedor'])->whereNull('deleted_at')->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
        $pagosCamiones = $pagosCamiones->get();
        $totalCompras = $pagosProveedor->sum(function ($pago) {return $this->convertirABob($pago->monto,$pago->moneda,$pago->tipo_cambio);});
        $totalVentas = $cobrosCliente->sum(function ($pago) {return $this->convertirABob($pago->monto,$pago->moneda ?? 'BOB',$pago->tipo_cambio ?? null);});
        $totalGastosExtras = $gastosExtras->sum('monto_bolivianos');
        $totalPagosCamiones = $pagosCamiones->sum(function ($pago) {return $this->convertirABob($pago->monto,$pago->moneda ?? 'BOB',$pago->tipo_cambio ?? null);});
        $gastosTotales = $totalCompras + $totalGastosExtras + $totalPagosCamiones;
        $margenBruto = $totalVentas - $gastosTotales;
        $rentabilidad = $totalVentas > 0 ? ($margenBruto / $totalVentas) * 100 : 0;
        $gastosPorCategoria = GastoExtra::whereNull('deleted_at')->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        if ($contratosIds->isNotEmpty()) {
            $gastosPorCategoria->whereIn('contrato_id', $contratosIds);
        }
        $gastosPorCategoria = $gastosPorCategoria->selectRaw('categoria, COUNT(*) as cantidad, SUM(monto_bolivianos) as total')->groupBy('categoria')->orderByDesc('total')->get();
        return view('reportes.index', compact('fechaInicio','fechaFin','proveedores','clientes','contratos','pagosProveedor','cobrosCliente','gastosExtras','pagosCamiones','totalCompras','totalVentas','totalGastosExtras','totalPagosCamiones','gastosTotales','margenBruto','rentabilidad','gastosPorCategoria'));
    }

    public function exportarExcel(Request $request)
    {
        return Excel::download(new ReporteGeneralExport($request),'reporte_general_' . now()->format('YmdHis') . '.xlsx');
    }

    private function convertirABob($monto, $moneda, $tipoCambio)
    {
        if ($moneda === 'BOB') {
            return $monto;
        }
        return $monto * ($tipoCambio ?? 0);
    }
}