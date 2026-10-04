<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Camion;
use App\Models\Tramo;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Parametro;
use App\Models\Contrato;

class SeguimientoCargasController extends Controller
{
    // Desde esta vista se registran pagos a camiones, que exigen token de idempotencia
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
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

        $entregadosQuery = (clone $base)->with('cliente')->where('estado', 'Entregado')
            ->when($request->filled('proveedor_id'), fn($q) => $q->whereHas('contratoCamion.contrato', fn($q2) => $q2->where('proveedor_id', $request->proveedor_id)))
            ->when($request->filled('tipo_tramo'), fn($q) => $q->where('tipo_tramo', $request->tipo_tramo))
            ->when($request->filled('contrato_numero'), fn($q) => $q->whereHas('contratoCamion.contrato', fn($q2) => $q2->where('numero_contrato', 'like', '%' . $request->contrato_numero . '%')))
            ->orderByDesc('fecha_llegada');

        // El estado de flete (pagado/pendiente/sin flete) se calcula con accessors PHP,
        // no es una columna: con ese filtro traemos todo y filtramos en PHP, sin paginar.
        if ($request->filled('flete_estado')) {
            $entregados = $entregadosQuery->get()->filter(function ($t) use ($request) {
                $cc = $t->contratoCamion;
                $estado = !$cc->monto_acordado ? 'sin_flete' : ($cc->saldo_pendiente > 0 ? 'pendiente' : 'pagado');
                return $estado === $request->flete_estado;
            })->values();
        } else {
            $entregados = $entregadosQuery->paginate(50, ['*'], 'pagina_entregados')->withQueryString();
        }

        $resumen = [
            'en_ruta'       => $enRuta->count(),
            'transbordando' => $transbordando->count(),
            'transbordado'  => $transbordado->count(),
            'entregado'     => (clone $entregadosQuery)->count(),
        ];

        // Resumen de contratos con/sin camiones asignados, por tipo y por proveedor,
        // para el texto que aparece sobre las pestañas al filtrar.
        $contratosConConteo = Contrato::whereNull('deleted_at')->withCount('contratoCamiones')->get();
        $resumirGrupo = fn($grupo) => [
            'total'      => $grupo->count(),
            'con_envios' => $conEnvios = $grupo->where('contrato_camiones_count', '>', 0)->count(),
            'sin_envios' => $grupo->count() - $conEnvios,
        ];
        $resumenContratosPorTipo      = $contratosConConteo->groupBy('tipo_contrato')->map($resumirGrupo);
        $resumenContratosPorProveedor = $contratosConConteo->groupBy('proveedor_id')->map($resumirGrupo);

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

        // Los modales de esta vista postean a otros controladores que validan
        // un token de idempotencia para evitar registros dobles.
        $idempotencyToken   = $this->generarToken('pago_camion_store_token');
        $tokenTransbordo    = $this->generarToken('tramo_transbordo_store_token');

        return view('seguimiento.index', compact('enRuta', 'transbordando', 'transbordado', 'entregados', 'resumen', 'resumenContratosPorTipo', 'resumenContratosPorProveedor', 'clientes', 'proveedores', 'empresas', 'camionesDisponibles', 'monedas', 'tramoErrorLlegada', 'idempotencyToken', 'tokenTransbordo'));
    }
}
