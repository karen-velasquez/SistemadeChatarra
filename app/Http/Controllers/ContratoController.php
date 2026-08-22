<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Contrato;
use App\Models\Tramo;
use App\Models\Cliente;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use App\Models\OperadorTransporte;
use App\Models\Empresa;
use App\Models\LoteEntrega;
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
                            'contratoCamiones.tramos.tramosHijos',
                            'contratoCamiones.tramos.cliente',
                            'contratoCamiones.tramos.camion',
                            'usuarioCreador',
                            'usuarioActualizador',
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

        // Datos planos para el botón "Descargar Excel": una fila por cada entrega
        // (tramo final entregado a un cliente, con su propio precio de venta y placa),
        // más una fila de subtotal por contrato. Fórmula acordada con el cliente:
        //   Total ventas   = Tn entregadas x Precio de venta
        //   Importe compra = Tn entregadas x costo_unitario del contrato (prorrateo por tonelaje)
        //   Utilidad Bruta = Total ventas - Importe compra
        //   IT             = Total ventas x 3%
        //   Comisión 1     = Total ventas x 3%
        //   Comisión 2 ZPL = Total ventas x 1,1%
        //   Utilidad Neta  = Utilidad Bruta - IT - Comisión 1 - Comisión 2
        $contratosExcelData = collect();

        foreach ($contratos as $c) {
            $entregas = collect();
            foreach ($c->contratoCamiones as $cc) {
                foreach ($cc->tramos as $t) {
                    if ($t->tramosHijos->isNotEmpty() || $t->estado !== 'Entregado') continue;
                    $entregas->push([
                        'placa'          => $cc->camion->placa ?? '',
                        'cliente'        => $t->cliente->nombre ?? '',
                        'tn_entregadas'  => (float) $t->peso_llegada,
                        'precio_venta'   => (float) $t->precio_por_tonelada,
                    ]);
                }
            }

            $tnTotales = $entregas->sum('tn_entregadas');
            $costoUnitario = (float) $c->costo_unitario;

            $filaBase = [
                'numero_contrato'  => $c->numero_contrato,
                'tipo_contrato'    => $c->tipo_contrato,
                'proveedor'        => $c->proveedor->nombre ?? '',
                'moneda'           => $c->moneda,
            ];

            $sumaVentas = 0;
            $sumaCompras = 0;
            $sumaIt = 0;
            $sumaCom1 = 0;
            $sumaCom2 = 0;
            $sumaUtilNeta = 0;

            foreach ($entregas as $e) {
                $totalVentas   = round($e['tn_entregadas'] * $e['precio_venta'], 2);
                $importeCompra = $tnTotales > 0 ? round($e['tn_entregadas'] * $costoUnitario, 2) : 0;
                $utilidadBruta = round($totalVentas - $importeCompra, 2);
                $it            = round($totalVentas * 0.03, 2);
                $comision1     = round($totalVentas * 0.03, 2);
                $comision2     = round($totalVentas * 0.011, 2);
                $utilidadNeta  = round($utilidadBruta - $it - $comision1 - $comision2, 2);

                $sumaVentas   += $totalVentas;
                $sumaCompras  += $importeCompra;
                $sumaIt       += $it;
                $sumaCom1     += $comision1;
                $sumaCom2     += $comision2;
                $sumaUtilNeta += $utilidadNeta;

                $contratosExcelData->push($filaBase + [
                    'placa'            => $e['placa'],
                    'cliente'          => $e['cliente'],
                    'tn_entregadas'    => $e['tn_entregadas'],
                    'precio_venta'     => $e['precio_venta'],
                    'total_ventas'     => $totalVentas,
                    'importe_compra'   => $importeCompra,
                    'utilidad_bruta'   => $utilidadBruta,
                    'it_3'             => $it,
                    'comision_1_3'     => $comision1,
                    'comision_2_zpl'   => $comision2,
                    'utilidad_neta'    => $utilidadNeta,
                    'es_subtotal'      => false,
                ]);
            }

            // Fila de subtotal del contrato (solo si tuvo alguna entrega).
            // numero_contrato se mantiene real (el frontend filtra por él para
            // saber qué contratos están visibles en pantalla); al armar el Excel
            // esa columna se vacía y se combina con Tipo/Proveedor en una sola
            // celda con el texto "SUBTOTAL {número}" (ver _exportarXlsx).
            if ($entregas->isNotEmpty()) {
                $contratosExcelData->push($filaBase + [
                    'placa'            => '',
                    'cliente'          => 'SUBTOTAL ' . $c->numero_contrato,
                    'tn_entregadas'    => $tnTotales,
                    'precio_venta'     => '',
                    'total_ventas'     => round($sumaVentas, 2),
                    'importe_compra'   => round($sumaCompras, 2),
                    'utilidad_bruta'   => round($sumaVentas - $sumaCompras, 2),
                    'it_3'             => round($sumaIt, 2),
                    'comision_1_3'     => round($sumaCom1, 2),
                    'comision_2_zpl'   => round($sumaCom2, 2),
                    'utilidad_neta'    => round($sumaUtilNeta, 2),
                    'estado_envios'    => $c->envios_cerrados ? 'Envíos cerrados' : 'Envíos abiertos',
                    'es_subtotal'      => true,
                ]);
            } else {
                // Sin entregas registradas todavía: una fila informativa sin cálculos
                $contratosExcelData->push($filaBase + [
                    'placa'            => '',
                    'cliente'          => '',
                    'tn_entregadas'    => 0,
                    'precio_venta'     => '',
                    'total_ventas'     => '',
                    'importe_compra'   => '',
                    'utilidad_bruta'   => '',
                    'it_3'             => '',
                    'comision_1_3'     => '',
                    'comision_2_zpl'   => '',
                    'utilidad_neta'    => '',
                    'es_subtotal'      => false,
                ]);
            }
        }

        $contratosExcelData = $contratosExcelData->values();

        return view('contratos.index', compact('contratos', 'clientes', 'proveedores', 'numeroSiguiente', 'idempotencyToken', 'contratosExcelData'));
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

        $contrato = Contrato::create($data);

        // Solo para proveedores NACIONALES se crea el lote semanal automáticamente
        $proveedor = Proveedor::find($contrato->proveedor_id);
        if ($proveedor?->tipo_proveedor === 'NACIONAL') {
            LoteEntrega::obtenerOCrearSemanaActual($contrato->proveedor_id);
        }

        Alert::success('Registro', 'Contrato registrado con éxito.');
        return redirect()->route('contratos.index');
    }

    public function edit($uuid)
    {
        $contrato = Contrato::with(['cliente', 'proveedor'])
                        ->where('uuid', $uuid)->firstOrFail();
        return response()->json($contrato);
    }

    // API: resumen de toneladas del contrato, para el modal de Registrar Llegada
    public function toneladas($id)
    {
        $contrato = Contrato::findOrFail($id);

        return response()->json([
            'numero_contrato'     => $contrato->numero_contrato,
            'fecha_inicio'        => $contrato->fecha_inicio?->format('d/m/Y'),
            'fecha_fin'           => $contrato->fecha_fin?->format('d/m/Y'),
            'toneladas_contrato'  => (float) $contrato->toneladas_contrato,
            'toneladas_entregadas'=> $contrato->toneladas_entregadas,
            'toneladas_en_transito' => $contrato->toneladas_en_transito,
        ]);
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
            // Obtener todos los tramos (raíz e hijos) incluyendo soft-deleted
            $tramos = $contratoCamion->tramos()->withTrashed()->get();

            // Eliminar documentos de entrega de cada tramo
            foreach ($tramos as $tramo) {
                if ($tramo->documento_entrega) {
                    Storage::disk('public')->delete($tramo->documento_entrega);
                }
            }

            $tramoIds = $tramos->pluck('id');

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

        // 6. Eliminar PDF del contrato
        if ($contrato->documento_pdf) {
            Storage::disk('public')->delete($contrato->documento_pdf);
        }

        $contrato->delete();
        Alert::success('Eliminación', 'Contrato y todos sus registros asociados eliminados con éxito.');
        return redirect()->route('contratos.index');
    }

    public function cerrarEnvios($uuid)
    {
        $contrato = Contrato::where('uuid', $uuid)->firstOrFail();
        $retorno  = $this->retornoTrasToggleEnvios($uuid);

        if ($contrato->envios_cerrados) {
            Alert::warning('Aviso', 'Los envíos de este contrato ya están cerrados.');
            return $retorno;
        }

        $contrato->update([
            'envios_cerrados'    => true,
            'envios_cerrados_at' => Carbon::now(),
            'updated_by'         => auth()->id(),
        ]);

        Alert::success('Cierre de Envíos', "Contrato {$contrato->numero_contrato}: envíos cerrados. Ya no se pueden agregar más camiones.");
        return $retorno;
    }

    public function descerrarEnvios($uuid)
    {
        $contrato = Contrato::where('uuid', $uuid)->firstOrFail();
        $retorno  = $this->retornoTrasToggleEnvios($uuid);

        if (!$contrato->envios_cerrados) {
            Alert::warning('Aviso', 'Los envíos de este contrato no están cerrados.');
            return $retorno;
        }

        // Reabrir: el contrato vuelve a estar editable y sale de la liquidación
        // (la liquidación solo lista contratos con envios_cerrados = true).
        $contrato->update([
            'envios_cerrados'    => false,
            'envios_cerrados_at' => null,
            'updated_by'         => auth()->id(),
        ]);

        Alert::success('Envíos Reabiertos', "Contrato {$contrato->numero_contrato}: envíos reabiertos. Vuelve a estar disponible para agregar camiones y ya no aparece en liquidación.");
        return $retorno;
    }

    // Si la acción vino desde la pantalla de Gestión de Camiones, vuelve ahí; si no, al listado.
    private function retornoTrasToggleEnvios($uuid)
    {
        return request('origen') === 'camiones'
            ? redirect()->route('contratos.camiones', $uuid)
            : redirect()->route('contratos.index');
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

        $clientes = Cliente::with(['pais', 'contacts' => fn($q) => $q->where('tipo', 'direccion')->whereNull('deleted_at')])->whereNull('deleted_at')->orderBy('nombre')->get();

        $monedas = Parametro::where('tipo', 'tipo_moneda')->orderBy('valor')->get();

        $empresas = Empresa::whereNull('deleted_at')->orderBy('nombre')->get();

        $tokenContratoCamion     = $this->generarToken('contrato_camion_store_token');
        $tokenTramoTransbordo    = $this->generarToken('tramo_transbordo_store_token');

        $tramoErrorLlegada = null;
        if (session('abrirModalLlegada')) {
            $tramoErrorLlegada = Tramo::where('uuid', session('abrirModalLlegada'))->first();
        }

        return view('contratos.camiones', compact('contrato', 'camionesDisponibles', 'choferes', 'clientes', 'monedas', 'empresas', 'tokenContratoCamion', 'tokenTramoTransbordo', 'tramoErrorLlegada'));
    }
}
