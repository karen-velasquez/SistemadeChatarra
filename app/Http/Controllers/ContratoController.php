<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Contrato;
use App\Models\Cliente;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use App\Models\OperadorTransporte;
use App\Models\Parametro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Http\Requests\ContratoRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ContratoController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $contratos  = Contrato::with([
                            'proveedor.pais',
                            'contratoCamiones.tramos',
                        ])
                        ->whereNull('deleted_at')
                        ->orderByDesc('created_at')
                        ->get();

        $clientes   = Cliente::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();

        $proveedores = Proveedor::with('pais')
                        ->whereNull('deleted_at')
                        ->orderBy('nombre')
                        ->get();

        $numeroSiguiente = Contrato::generarNumero();

        $idempotencyToken = Str::uuid()->toString();
        session(['contrato_store_token' => $idempotencyToken]);

        return view('contratos.index', compact('contratos', 'clientes', 'proveedores', 'numeroSiguiente', 'idempotencyToken'));
    }

    public function nuevoToken()
    {
        $token = \Illuminate\Support\Str::uuid()->toString();
        session(['contrato_store_token' => $token]);
        return response()->json(['token' => $token]);
    }

    public function store(ContratoRequest $request)
    {
        $tokenEnviado   = $request->input('_idempotency_token');
        $tokenEnSesion  = session('contrato_store_token');

        if (!$tokenEnviado || $tokenEnviado !== $tokenEnSesion) {
            Alert::error('Solicitud duplicada', 'Este contrato ya fue registrado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('contratos.index');
        }

        session()->forget('contrato_store_token');

        $data = $request->except(['documento_pdf', '_idempotency_token']);

        if ($request->hasFile('documento_pdf')) {
            $data['documento_pdf'] = $request->file('documento_pdf')
                ->store('contratos', 'public');
        }

        Contrato::create($data);
        Alert::success('Registro', 'Contrato registrado con éxito.');
        return redirect()->route('contratos.index');
    }

    public function edit($uuid)
    {
        $contrato = Contrato::with(['cliente', 'proveedor'])
                        ->where('uuid', $uuid)->firstOrFail();
        return response()->json($contrato);
    }

    public function update(ContratoRequest $request, Contrato $contrato)
    {
        if ($contrato->envios_cerrados) {
            Alert::error('No permitido', 'No se puede modificar un contrato con envíos cerrados.');
            return redirect()->route('contratos.index');
        }

        $data = $request->except('documento_pdf');

        if ($request->hasFile('documento_pdf')) {
            // Eliminar el PDF anterior si existe
            if ($contrato->documento_pdf) {
                Storage::disk('public')->delete($contrato->documento_pdf);
            }
            $data['documento_pdf'] = $request->file('documento_pdf')
                ->store('contratos', 'public');
        }

        $contrato->update($data);
        Alert::success('Actualización', 'Contrato actualizado con éxito.');
        return redirect()->route('contratos.index');
    }

    public function verPdf($uuid)
    {
        $contrato = Contrato::where('uuid', $uuid)->firstOrFail();

        abort_if(!$contrato->documento_pdf, 404, 'Este contrato no tiene documento adjunto.');

        $path = Storage::disk('public')->path($contrato->documento_pdf);

        abort_if(!file_exists($path), 404, 'Archivo no encontrado.');

        // Detecta el tipo real del archivo (PDF o imagen) para mostrarlo correctamente.
        return response()->file($path, ['Content-Type' => mime_content_type($path)]);
    }

    public function destroy($uuid)
    {
        $contrato = Contrato::with('contratoCamiones')->where('uuid', $uuid)->firstOrFail();

        // Eliminación en cascada respetando el orden de FKs.
        // Se usa forceDelete() para quitar físicamente las filas; de lo contrario
        // el soft-delete deja los registros en la tabla y la FK sigue bloqueando al padre.
        foreach ($contrato->contratoCamiones as $contratoCamion) {
            // Obtener todos los IDs de tramos (raíz e hijos) de este contrato camión,
            // incluyendo los que ya estén soft-deleted
            $tramoIds = $contratoCamion->tramos()->withTrashed()->pluck('id');

            // 1. Eliminar pagos de cliente (dependen de tramos)
            PagoCliente::withTrashed()->whereIn('tramo_id', $tramoIds)->forceDelete();

            // 2. Eliminar tramos (dependen de contrato_camiones)
            $contratoCamion->tramos()->withTrashed()->forceDelete();

            // 3. Eliminar pagos de camión (dependen de contrato_camiones)
            PagoCamion::withTrashed()->where('contrato_camion_id', $contratoCamion->id)->forceDelete();

            // 4. Eliminar el contrato camión (físico, no usa SoftDeletes)
            $contratoCamion->delete();
        }

        // 5. Eliminar pagos de proveedor (dependen de contratos)
        PagoProveedor::withTrashed()->where('contrato_id', $contrato->id)->forceDelete();

        $contrato->delete();
        Alert::success('Eliminación', 'Contrato y todos sus registros asociados eliminados con éxito.');
        return redirect()->route('contratos.index');
    }

    public function cerrarEnvios($uuid)
    {
        $contrato = Contrato::where('uuid', $uuid)->firstOrFail();

        if ($contrato->envios_cerrados) {
            Alert::warning('Aviso', 'Los envíos de este contrato ya están cerrados.');
            return redirect()->route('contratos.index');
        }

        $contrato->update([
            'envios_cerrados'    => true,
            'envios_cerrados_at' => Carbon::now(),
            'updated_by'         => auth()->id(),
        ]);

        Alert::success('Cierre de Envíos', "Contrato {$contrato->numero_contrato}: envíos cerrados. Ya no se pueden agregar más camiones.");
        return redirect()->route('contratos.index');
    }

    public function descerrarEnvios($uuid)
    {
        $contrato = Contrato::where('uuid', $uuid)->firstOrFail();

        if (!$contrato->envios_cerrados) {
            Alert::warning('Aviso', 'Los envíos de este contrato no están cerrados.');
            return redirect()->route('contratos.index');
        }

        // Reabrir: el contrato vuelve a estar editable y sale de la liquidación
        // (la liquidación solo lista contratos con envios_cerrados = true).
        $contrato->update([
            'envios_cerrados'    => false,
            'envios_cerrados_at' => null,
            'updated_by'         => auth()->id(),
        ]);

        Alert::success('Envíos Reabiertos', "Contrato {$contrato->numero_contrato}: envíos reabiertos. Vuelve a estar disponible para agregar camiones y ya no aparece en liquidación.");
        return redirect()->route('contratos.index');
    }

    public function liquidacion()
    {
        // Contratos cerrados con sus relaciones para liquidación
        $contratos = Contrato::with([
                'proveedor',
                'contratoCamiones.tramos',
            ])
            ->whereNull('deleted_at')
            ->where('envios_cerrados', true)
            ->orderBy('proveedor_id')
            ->orderByDesc('envios_cerrados_at')
            ->get();

        // Agrupar por proveedor
        $porProveedor = $contratos->groupBy('proveedor_id')->map(function ($ctrs) {
            $proveedor       = $ctrs->first()->proveedor;
            $totalPactado    = $ctrs->sum('toneladas_contrato');
            $totalDeclarado  = $ctrs->sum(fn($c) => $c->toneladas_declaradas);
            $totalEntregado  = $ctrs->sum(fn($c) => $c->toneladas_entregadas);
            $diferenciaNeta  = round($totalEntregado - $totalDeclarado, 3);
            $diferenciaPactadoLlegado = round($totalEntregado - $totalPactado, 3);

            return [
                'proveedor'        => $proveedor,
                'contratos'        => $ctrs,
                'total_pactado'    => $totalPactado,
                'total_declarado'  => $totalDeclarado,
                'total_entregado'  => $totalEntregado,
                'diferencia_neta'  => $diferenciaNeta,
                'diferencia_pactado_llegado' => $diferenciaPactadoLlegado,
            ];
        });

        return view('contratos.liquidacion', compact('porProveedor'));
    }

    public function camiones($uuid)
    {
        $contrato = Contrato::with([
            'proveedor',
            'contratoCamiones.camion.marca',
            'contratoCamiones.camion.tipoVehiculo',
            'contratoCamiones.camion.placaPais',
            'contratoCamiones.conductor',
            'contratoCamiones.tramos.camion.marca',
            'contratoCamiones.tramos.camion.tipoVehiculo',
            'contratoCamiones.tramos.camion.placaPais',
            'contratoCamiones.tramos.conductor',
            'contratoCamiones.tramos.tramosHijos.camion.marca',
            'contratoCamiones.tramos.tramosHijos.camion.tipoVehiculo',
            'contratoCamiones.tramos.tramosHijos.camion.placaPais',
            'contratoCamiones.tramos.tramosHijos.conductor',
            'contratoCamiones.tramos.tramoPadre',
        ])->where('uuid', $uuid)->firstOrFail();

        // Filtrar ContratoCamiones que NO son hijos de una división/transbordo
        // Un ContratoCamion es "hijo" si TODOS sus tramos tienen un padre con estado "Div. Carga" o "Transbord*"
        $contrato->setRelation('contratoCamiones', $contrato->contratoCamiones->filter(function($cc) {
            // Si no tiene tramos, mantenerlo
            if ($cc->tramos->isEmpty()) {
                return true;
            }

            // Verificar si TODOS los tramos de este CC son hijos de un padre con división o transbordo
            $todosHijosDeTransferencia = $cc->tramos->every(function($tramo) {
                // Si el tramo no tiene padre, no es hijo de transferencia
                if (!$tramo->tramo_padre_id) {
                    return false;
                }

                // Verificar si el padre tiene estado "Div. Carga", "Transbordando" o "Transbordado"
                $tramoPadre = $tramo->tramoPadre;
                return $tramoPadre && in_array($tramoPadre->estado, ['Div. Carga', 'Transbordando', 'Transbordado']);
            });

            // Si todos los tramos son hijos de transferencia, excluir este ContratoCamion
            return !$todosHijosDeTransferencia;
        }));

        $camionesDisponibles = Camion::with(['conductorActual.conductor', 'marca', 'tipoVehiculo', 'placaPais'])
            ->whereNull('deleted_at')
            ->where('estado', 'Activo')
            ->orderBy('placa')
            ->get();

        $choferes = OperadorTransporte::whereNull('deleted_at')
            ->whereIn('tipo_operador', ['chofer', 'ambos'])
            ->whereNotNull('licencia_numero')
            ->orderBy('nombre')
            ->get();

        $clientes = Cliente::with('pais')->whereNull('deleted_at')->orderBy('nombre')->get();

        $monedas = Parametro::where('tipo', 'tipo_moneda')->orderBy('valor')->get();

        $tokenContratoCamion     = $this->generarToken('contrato_camion_store_token');
        $tokenTramoTransbordo    = $this->generarToken('tramo_transbordo_store_token');

        return view('contratos.camiones', compact('contrato', 'camionesDisponibles', 'choferes', 'clientes', 'monedas', 'tokenContratoCamion', 'tokenTramoTransbordo'));
    }
}
