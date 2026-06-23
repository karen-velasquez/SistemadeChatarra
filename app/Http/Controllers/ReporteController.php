<?php

namespace App\Http\Controllers;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\GastoExtra;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use App\Models\CuentaEmpresa;
use App\Models\Movimiento;
use App\Exports\ReporteGeneralExport;
use Illuminate\Http\Request;
use App\Exports\CapitalUtilidadExport;
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
        $cobrosCliente = PagoCliente::with(['tramo.contratoCamion.contrato.cliente'])->whereNull('deleted_at')->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]); 
        $cobrosCliente = $cobrosCliente->get();
        $gastosExtras = GastoExtra::with(['contrato.proveedor'])->whereNull('deleted_at')->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        if ($contratosIds->isNotEmpty()) {
            $gastosExtras->whereIn('contrato_id', $contratosIds);
        }
        $gastosExtras = $gastosExtras->get();
        $pagosCamiones = PagoCamion::with(['contratoCamion.contrato.proveedor'])->whereNull('deleted_at')->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
        $pagosCamiones = $pagosCamiones->get();
        $totalCompras = $pagosProveedor->sum(function ($pago) {return $this->convertirABob($pago->monto,$pago->moneda_pago,$pago->tipo_cambio);});
        $totalVentas = $cobrosCliente->sum(function ($pago) {return $this->convertirABob($pago->monto,$pago->moneda_pago ?? 'BOB',$pago->tipo_cambio ?? null);});
        $totalGastosExtras = $gastosExtras->sum('monto_bolivianos');
        $totalPagosCamiones = $pagosCamiones->sum(function ($pago) {return $this->convertirABob($pago->monto,$pago->moneda_pago ?? 'BOB',$pago->tipo_cambio ?? null);});
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
    public function capitalUtilidad(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
        $cuentaId = $request->cuenta_empresa_id ?? 'todas';
        $cuentas = CuentaEmpresa::with('banco')->whereNull('deleted_at')->orderBy('nombre_cuenta')->get();
        $cuentasFiltradas = CuentaEmpresa::query();
        if ($cuentaId !== 'todas') {
            $cuentasFiltradas->where('id', $cuentaId);
        }
        $cuentasFiltradas = $cuentasFiltradas->get();
        $capitalInicial = $cuentasFiltradas->sum('saldo_inicial');
        $capitalActual = $cuentasFiltradas->sum('saldo_actual');
        $movimientos = Movimiento::with('cuentaEmpresa.banco')->whereNull('deleted_at')->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($cuentaId !== 'todas') {
            $movimientos->where('cuenta_empresa_id', $cuentaId);
        }

        $movimientos = $movimientos->get();
        $ventasClientes = $movimientos->where('tipo', 'ingreso')->whereIn('categoria', ['pago_cliente', 'anticipo_cliente'])->sum('monto_bolivianos');
        $pagosProveedores = $movimientos->where('tipo', 'egreso')->where('categoria', 'pago_proveedor')->sum('monto_bolivianos');
        $pagosCamiones = $movimientos->where('tipo', 'egreso')->where('categoria', 'pago_camion')->sum('monto_bolivianos');
        $gastosExtras = $movimientos->where('tipo', 'egreso')->where('categoria', 'gasto_extra')->sum('monto_bolivianos');
        $otrosIngresos = $movimientos->where('tipo', 'ingreso')->whereNotIn('categoria', ['pago_cliente', 'anticipo_cliente'])->sum('monto_bolivianos');
        $otrosEgresos = $movimientos->where('tipo', 'egreso')->whereNotIn('categoria', ['pago_proveedor', 'pago_camion', 'gasto_extra'])->sum('monto_bolivianos');
        $capitalDespuesProveedores = $capitalInicial - $pagosProveedores;
        $capitalFinalCalculado = $capitalInicial + $ventasClientes + $otrosIngresos - $pagosProveedores - $pagosCamiones - $gastosExtras - $otrosEgresos;
        $utilidadOperativa = $ventasClientes - $pagosProveedores - $pagosCamiones - $gastosExtras;
        $utilidadNeta = $ventasClientes + $otrosIngresos - $pagosProveedores - $pagosCamiones - $gastosExtras - $otrosEgresos;
        $resumenPorCuenta = [];

        foreach ($cuentas as $cuenta) {
            $movs = Movimiento::where('cuenta_empresa_id', $cuenta->id)->whereNull('deleted_at')->whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
            $ingresos = $movs->where('tipo', 'ingreso')->sum('monto_bolivianos');
            $egresos = $movs->where('tipo', 'egreso')->sum('monto_bolivianos');
            $resumenPorCuenta[] = [
                'cuenta' => $cuenta->nombre_cuenta,
                'banco' => $cuenta->banco->nombre ?? '',
                'moneda' => $cuenta->moneda,
                'saldo_inicial' => $cuenta->saldo_inicial,
                'saldo_actual' => $cuenta->saldo_actual,
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'utilidad' => $ingresos - $egresos,
            ];
        }

        $gastosDetalle = GastoExtra::with(['contrato.proveedor','contrato.cliente'])->whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
        return view('reportes.capital_utilidad',compact('fechaInicio','fechaFin','cuentaId','cuentas','capitalInicial','capitalActual','ventasClientes','pagosProveedores','pagosCamiones', 'gastosExtras','otrosIngresos','otrosEgresos','capitalDespuesProveedores','capitalFinalCalculado','utilidadOperativa','utilidadNeta','resumenPorCuenta','gastosDetalle'));
    }

    public function capitalUtilidadExcel(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
        $cuentaId = $request->cuenta_empresa_id ?? 'todas';
        return Excel::download(new CapitalUtilidadExport($fechaInicio, $fechaFin, $cuentaId),'reporte_capital_utilidad_' . now()->format('Ymd_His') . '.xlsx');
    }
    public function exportarExcel(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
        $proveedorId = $request->proveedor_id ?? null;
        $clienteId = $request->cliente_id ?? null;
        $tipoContrato = $request->tipo_contrato ?? null;

        return Excel::download(
            new ReporteGeneralExport(
                $fechaInicio,
                $fechaFin,
                $proveedorId,
                $clienteId,
                $tipoContrato
            ),
            'reporte_general_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function convertirABob($monto, $moneda, $tipoCambio)
    {
        if ($moneda === 'BOB') {
            return $monto;
        }
        return $monto * ($tipoCambio ?? 0);
    }
}