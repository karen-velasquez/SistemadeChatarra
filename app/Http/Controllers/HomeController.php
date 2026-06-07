<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Contrato;
use App\Models\GastoExtra;
use App\Models\CuentaBancaria;
use App\Models\PagoProveedor;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\Tramo;
use App\Models\Movimiento;
use App\Models\CuentaEmpresa;
use Carbon\Carbon;


class HomeController extends Controller
{
    public function index(Request $request)
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $contratosActivos = Contrato::whereNull('deleted_at')->count();
        $proveedoresRegistrados = Proveedor::whereNull('deleted_at')->count();
        $cuentasActivas = CuentaBancaria::whereNull('deleted_at')->count();
        $gastosExtrasPagadosMes = GastoExtra::whereNull('deleted_at')->where('estado', 'pagado')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $gastosExtrasPendientesMes = GastoExtra::whereNull('deleted_at')->where('estado', 'pendiente')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $gastosPendientesTotal = GastoExtra::whereNull('deleted_at')->where('estado', 'pendiente')->sum('monto_bolivianos');
        $pagosPendientesCantidad = GastoExtra::whereNull('deleted_at')->where('estado', 'pendiente')->count();
        $pagosProveedorPagadosMes = PagoProveedor::whereNull('deleted_at')->whereBetween('fecha_pago', [$inicioMes, $finMes])->get()->sum(function ($pago) {return $pago->moneda === 'BOB' ? $pago->monto : $pago->monto * $pago->tipo_cambio;});
        $pagosProveedorPendientesMes = PagoProveedor::whereNull('deleted_at')->whereBetween('fecha_pago', [$inicioMes, $finMes])->get()->sum(function ($pago) {return $pago->moneda === 'BOB' ? $pago->monto : $pago->monto * $pago->tipo_cambio;});
        $cobrosClientesPagadosMes = PagoCliente::whereNull('deleted_at')->whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto');
        $cobrosClientesPendientesMes = PagoCliente::whereNull('deleted_at')->whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto');
        $pagosCamionesPagadosMes = PagoCamion::whereNull('deleted_at')->whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto');
        $pagosCamionesPendientesMes = PagoCamion::whereNull('deleted_at')->whereBetween('fecha_pago', [$inicioMes, $finMes])->sum('monto');
        $camionesPorEstado = Tramo::whereNull('deleted_at')->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $camionesTransbordado = $camionesPorEstado['TRANSBORDADO'] ?? 0;
        $camionesEnRuta = $camionesPorEstado['EN RUTA'] ?? 0;
        $camionesDescargado = $camionesPorEstado['DESCARGADO'] ?? 0;
        $camionesPendiente = $camionesPorEstado['PENDIENTE'] ?? 0;
        $contratosRecientes = Contrato::with('proveedor')->whereNull('deleted_at')->latest()->take(5)->get();
        $pagosPendientes = GastoExtra::with('contrato.proveedor')->whereNull('deleted_at')->where('estado', 'pendiente')->orderBy('fecha', 'asc')->take(5)->get();
        $gastosPorCategoria = GastoExtra::whereNull('deleted_at')->where('estado', 'pagado')->selectRaw('categoria, SUM(monto_bolivianos) as total')->groupBy('categoria')->orderByDesc('total')->take(5)->get();
        $ultimosPagos = GastoExtra::with('contrato.proveedor')->whereNull('deleted_at')->where('estado', 'pagado')->latest()->take(5)->get();
        $proveedoresRanking = Proveedor::orderBy('nombre')->get();
        $clientesRanking = Cliente::orderBy('nombre')->get();
        $inicioMes = now()->startOfMonth()->format('Y-m-d');
        $finMes = now()->endOfMonth()->format('Y-m-d');
        $contratosActivos = Contrato::whereNull('deleted_at')->where('estado', 'Activo')->count();
        $contratosConcluidos = Contrato::whereNull('deleted_at')->where('estado', 'Concluido')->count();
        $proveedoresActivos = Proveedor::whereNull('deleted_at')->count();
        $clientesActivos = Cliente::whereNull('deleted_at')->count();
        $saldoTesoreria = CuentaEmpresa::whereNull('deleted_at')->sum('saldo_actual');
        $capitalInicial = CuentaEmpresa::whereNull('deleted_at')->sum('saldo_inicial');
        $ingresosMes = Movimiento::whereNull('deleted_at')->where('tipo', 'ingreso')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $egresosMes = Movimiento::whereNull('deleted_at')->where('tipo', 'egreso')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $cobrosClientesMes = Movimiento::whereNull('deleted_at')->where('tipo', 'ingreso')->whereIn('categoria', ['pago_cliente', 'anticipo_cliente'])->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $pagosProveedorPagadosMes = Movimiento::whereNull('deleted_at')->where('tipo', 'egreso')->where('categoria', 'pago_proveedor')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $pagosCamionMes = Movimiento::whereNull('deleted_at')->where('tipo', 'egreso')->where('categoria', 'pago_camion')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $gastosExtrasPagadosMes = GastoExtra::whereNull('deleted_at')->where('estado', 'PAGADO')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $gastosExtrasPendientesMes = GastoExtra::whereNull('deleted_at')->where('estado', 'PENDIENTE')->whereBetween('fecha', [$inicioMes, $finMes])->sum('monto_bolivianos');
        $utilidadMes = $cobrosClientesMes - $pagosProveedorPagadosMes - $pagosCamionMes - $gastosExtrasPagadosMes;
        $pagosProveedorPendientesMes = Contrato::whereNull('deleted_at')->get()->sum(function ($contrato) {return $contrato->saldo_pendiente_proveedor ?? 0;});
        $camionesTransbordado = Tramo::whereNull('deleted_at')->where('estado', 'Transbordando')->count();
        $camionesEnRuta = Tramo::whereNull('deleted_at')->where('estado', 'En ruta')->count();
        $camionesDescargado = Tramo::whereNull('deleted_at')->where('estado', 'Entregado')->count();
        $toneladasDeclaradasMes = Tramo::whereNull('deleted_at')->whereBetween('fecha_salida', [$inicioMes, $finMes])->sum('peso_salida');
        $toneladasEntregadasMes = Tramo::whereNull('deleted_at')->where('estado', 'Entregado')->whereBetween('fecha_llegada', [$inicioMes, $finMes])->sum('peso_llegada');
        $cuentasActivas = CuentaEmpresa::whereNull('deleted_at')->where('activo', 1)->count();
        $contratosRecientes = Contrato::with(['proveedor', 'cliente'])->whereNull('deleted_at')->latest('id')->take(5)->get();
        $movimientosRecientes = Movimiento::with(['cuentaEmpresa.banco'])->whereNull('deleted_at')->latest('fecha')->latest('id')->take(8)->get();
        $cuentasResumen = CuentaEmpresa::with('banco')->whereNull('deleted_at')->orderByDesc('saldo_actual')->take(5)->get();
        $gastosPorCategoria = GastoExtra::select('categoria', DB::raw('SUM(monto_bolivianos) as total'))->whereNull('deleted_at')->where('estado', 'PAGADO')->groupBy('categoria')->orderByDesc('total')->take(5)->get();
        $pagosPendientes = GastoExtra::with(['contrato.proveedor'])->whereNull('deleted_at')->where('estado', 'PENDIENTE')->latest('fecha')->take(5)->get();
        $pagosPendientesCantidad = GastoExtra::whereNull('deleted_at')->where('estado', 'PENDIENTE')->count();
        $gastosPendientesTotal = GastoExtra::whereNull('deleted_at')->where('estado', 'PENDIENTE')->sum('monto_bolivianos');
        $ultimosPagos = GastoExtra::with(['contrato.proveedor'])->whereNull('deleted_at')->where('estado', 'PAGADO')->latest('fecha')->take(6)->get();
        return view('home', compact('contratosActivos','contratosConcluidos','proveedoresActivos','clientesActivos','saldoTesoreria','capitalInicial','ingresosMes','egresosMes','cobrosClientesMes','pagosProveedorPagadosMes','pagosProveedorPendientesMes','pagosCamionMes','gastosExtrasPagadosMes','gastosExtrasPendientesMes','utilidadMes','camionesTransbordado','camionesEnRuta','camionesDescargado','toneladasDeclaradasMes','toneladasEntregadasMes','cuentasActivas','contratosRecientes','movimientosRecientes','cuentasResumen','gastosPorCategoria','pagosPendientes','pagosPendientesCantidad','gastosPendientesTotal','ultimosPagos'));
      //  return view('home', compact('proveedoresRanking', 'clientesRanking', 'contratosActivos', 'proveedoresRegistrados', 'cuentasActivas', 'gastosExtrasPagadosMes', 'gastosExtrasPendientesMes', 'gastosPendientesTotal', 'pagosPendientesCantidad', 'pagosProveedorPagadosMes', 'pagosProveedorPendientesMes', 'cobrosClientesPagadosMes', 'cobrosClientesPendientesMes', 'pagosCamionesPagadosMes', 'pagosCamionesPendientesMes', 'camionesTransbordado', 'camionesEnRuta', 'camionesDescargado', 'camionesPendiente', 'contratosRecientes', 'pagosPendientes', 'gastosPorCategoria', 'ultimosPagos'));
    }
}