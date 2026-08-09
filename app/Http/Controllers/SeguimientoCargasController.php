<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Tramo;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Parametro;

class SeguimientoCargasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $base = Tramo::with([
            'camion',
            'conductor',
            'contratoCamion.contrato.proveedor',
            'contratoCamion.pagos',
            'contratoCamion.camion.propietario',
        ])->whereNull('deleted_at');

        $enRuta        = (clone $base)->where('estado', 'En ruta')->orderBy('fecha_salida')->get();
        $transbordando = (clone $base)->where('estado', 'Transbordando')->orderBy('fecha_salida')->get();
        $transbordado  = (clone $base)->where('estado', 'Transbordado')->orderByDesc('fecha_llegada')->get();
        $entregados    = (clone $base)->with('cliente')->where('estado', 'Entregado')->orderByDesc('fecha_llegada')->limit(50)->get();

        $resumen = [
            'en_ruta'       => $enRuta->count(),
            'transbordando' => $transbordando->count(),
            'transbordado'  => $transbordado->count(),
            'entregado'     => Tramo::whereNull('deleted_at')->where('estado', 'Entregado')->count(),
        ];

        $clientes = Cliente::with(['pais', 'contacts' => fn($q) => $q->where('tipo', 'direccion')->whereNull('deleted_at')])->whereNull('deleted_at')->orderBy('nombre')->get();

        $proveedores = Proveedor::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();

        $empresas = Empresa::with('cuentas.banco')->whereNull('deleted_at')->get();

        $camionesDisponibles = Camion::with(['conductorActual.conductor'])
            ->whereNull('deleted_at')
            ->where('estado', 'Activo')
            ->orderBy('placa')
            ->get();

        $monedas = Parametro::where('tipo', 'tipo_moneda')->orderBy('valor')->get();

        $tramoErrorLlegada = null;
        if (session('abrirModalLlegada')) {
            $tramoErrorLlegada = Tramo::with('contratoCamion.contrato.proveedor')
                ->where('uuid', session('abrirModalLlegada'))
                ->first();
        }

        return view('seguimiento.index', compact('enRuta', 'transbordando', 'transbordado', 'entregados', 'resumen', 'clientes', 'proveedores', 'empresas', 'camionesDisponibles', 'monedas', 'tramoErrorLlegada'));
    }
}
