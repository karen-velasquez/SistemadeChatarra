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
use App\Models\ReglaComision;
use App\Models\ReglaComision2;
use App\Models\ReglaIt;
use App\Models\ReglaCostoAdicional;
use App\Models\GastoExtra;
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
                            'contratoCamiones.tramos.pagosCliente',
                            'contratoCamiones.tramos.empresaFacturadora',
                            'contratoCamiones.pagos.usuarioCreador',
                            'contratoCamiones.pagos.usuarioActualizador',
                            'pagosProveedor',
                            'gastosExtras.usuarioCreador',
                            'gastosExtras.usuarioActualizador',
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
        //   IT             = Tn entregadas x monto de la Regla de IT vigente
        //                    (cliente + empresa facturadora, según fecha de entrega);
        //                    0 si no hay ninguna regla vigente para esa venta.
        //   Comisión 1     = Tn entregadas x monto de la Regla de Comisión 1 vigente
        //                    (cliente + empresa facturadora, según fecha de entrega);
        //                    0 si no hay ninguna regla vigente para esa venta.
        //   Comisión 2 ZPL = Tn entregadas x monto de la Regla de Comisión 2 vigente
        //                    (cliente + empresa facturadora, según fecha de entrega);
        //                    0 si no hay ninguna regla vigente para esa venta.
        //   Costo adicional = monto de la Regla de Costo Adicional vigente
        //                    (cliente + empresa facturadora, según fecha de entrega);
        //                    0 si no hay ninguna regla vigente para esa venta.
        //   Utilidad Neta (por entrega) = Utilidad Bruta - IT - Comisión 1 - Comisión 2 - Costo adicional
        //   Utilidad Neta (SUBTOTAL del contrato) = suma de lo anterior - Gastos Extra
        //                    PAGADOS asociados al contrato (costo real del contrato, no de una entrega puntual)
        $contratosExcelData = collect();

        foreach ($contratos as $c) {
            $entregas = collect();
            $cobrosCliente = collect();
            foreach ($c->contratoCamiones as $cc) {
                foreach ($cc->tramos as $t) {
                    if ($t->tramosHijos->isNotEmpty() || $t->estado !== 'Entregado') continue;

                    $entregas->push([
                        'placa'          => $cc->camion->placa ?? '',
                        'cliente'        => $t->cliente->nombre ?? '',
                        'cliente_id'     => $t->cliente_id,
                        'empresa_facturadora_id'     => $t->empresa_facturadora_id,
                        'empresa_facturadora_nombre' => $t->empresaFacturadora->nombre ?? '',
                        'tn_entregadas'  => (float) $t->peso_llegada,
                        'precio_venta'   => (float) $t->precio_por_tonelada,
                        'fecha_entrega'  => $t->fecha_llegada?->format('Y-m-d') ?? '',
                    ]);

                    // Un cobro puede venir en moneda distinta a BOB: se convierte
                    // aquí mismo (monto x su propio tipo_cambio) para que cada
                    // cobro sea su propia fila en el Excel, no una suma ciega
                    // que mezclaría tipos de cambio distintos en una sola celda.
                    foreach ($t->pagosCliente as $p) {
                        if ($p->deleted_at) continue;
                        $tc = $p->moneda_pago === 'BOB' ? 1 : ((float) $p->tipo_cambio ?: 1);
                        $montoBs = $p->moneda_pago === 'BOB' ? (float) $p->monto : round((float) $p->monto * $tc, 2);
                        $cobrosCliente->push([
                            'cliente'      => $t->cliente->nombre ?? '',
                            'monto_bs'     => $montoBs,
                            'tipo_cambio'  => $tc,
                            'codigo'       => $p->codigo_seguimiento ?? '',
                            'fecha'        => $p->fecha_pago ? $p->fecha_pago->format('Y-m-d') : '',
                        ]);
                    }
                }
            }
            $montoPagadoProveedor = 0;
            $codigosPagoProveedor = [];
            $fechasPagoProveedor = [];
            foreach ($c->pagosProveedor as $p) {
                if ($p->deleted_at) continue;
                $tcP = $p->moneda_pago === 'BOB' ? 1 : ((float) $p->tipo_cambio ?: 1);
                $montoPagadoProveedor += $p->moneda_pago === 'BOB' ? (float) $p->monto : round((float) $p->monto * $tcP, 2);
                if ($p->codigo_seguimiento) $codigosPagoProveedor[] = $p->codigo_seguimiento;
                if ($p->fecha_pago) $fechasPagoProveedor[] = $p->fecha_pago->format('d/m/Y');
            }

            $tnTotales = $entregas->sum('tn_entregadas');
            // La línea de venta del contrato es la única que seguía en moneda
            // extranjera: se convierte a Bs con el TC aproximado del contrato
            // (mismo criterio ya aplicado a pagos/cobros/gastos extra), para que
            // todo el Excel quede consistente en bolivianos.
            $tcContrato    = $c->moneda === 'BOB' ? 1 : ((float) $c->tipo_cambio ?: 1);
            $costoUnitario = (float) $c->costo_unitario * $tcContrato;

            $filaBase = [
                'numero_contrato'  => $c->numero_contrato,
                'fecha_contrato'   => $c->fecha_inicio?->format('Y-m-d') ?? '',
                'tipo_contrato'    => $c->tipo_contrato,
                'proveedor'        => $c->proveedor->nombre ?? '',
                'moneda'           => 'BOB',
                'fecha_registro'   => $c->created_at?->format('d/m/Y H:i') ?? '',
                'registrado_por'   => $c->usuarioCreador->name ?? '',
                'fecha_edicion'    => $c->updated_at && !$c->updated_at->equalTo($c->created_at) ? $c->updated_at->format('d/m/Y H:i') : '',
                'editado_por'      => $c->updated_at && !$c->updated_at->equalTo($c->created_at) ? ($c->usuarioActualizador->name ?? '') : '',
            ];

            $sumaVentas = 0;
            $sumaCompras = 0;
            $sumaIt = 0;
            $sumaCom1 = 0;
            $sumaCom2 = 0;
            $sumaCostoAdicional = 0;
            $sumaUtilNeta = 0;
            $sumaMontoCobrado = 0;
            $sumaGastoExtra = 0;

            foreach ($entregas as $e) {
                $precioVentaBs = $e['precio_venta'] * $tcContrato;
                $totalVentas   = round($e['tn_entregadas'] * $precioVentaBs, 2);
                $importeCompra = $tnTotales > 0 ? round($e['tn_entregadas'] * $costoUnitario, 2) : 0;
                $utilidadBruta = round($totalVentas - $importeCompra, 2);
                $pctIt           = ReglaIt::porcentajeParaVenta($e['cliente_id'], $e['empresa_facturadora_id'], $e['fecha_entrega'] ?: null);
                $it              = round($totalVentas * $pctIt / 100, 2);
                $montoRegla      = ReglaComision::montoParaVenta($e['cliente_id'], $e['empresa_facturadora_id'], $e['fecha_entrega'] ?: null);
                $comision1       = round($e['tn_entregadas'] * $montoRegla, 2);
                $pctComision2    = ReglaComision2::porcentajeParaVenta($e['cliente_id'], $e['empresa_facturadora_id'], $e['fecha_entrega'] ?: null);
                $comision2       = round($totalVentas * $pctComision2 / 100, 2);
                $costoAdicional  = ReglaCostoAdicional::montoParaVenta($e['cliente_id'], $e['empresa_facturadora_id'], $e['fecha_entrega'] ?: null);
                $utilidadNeta  = round($utilidadBruta - $it - $comision1 - $comision2 - $costoAdicional, 2);

                $sumaVentas   += $totalVentas;
                $sumaCompras  += $importeCompra;
                $sumaIt       += $it;
                $sumaCom1     += $comision1;
                $sumaCom2     += $comision2;
                $sumaCostoAdicional += $costoAdicional;
                $sumaUtilNeta += $utilidadNeta;

                $contratosExcelData->push($filaBase + [
                    'placa'            => $e['placa'],
                    'empresa_facturadora' => $e['empresa_facturadora_nombre'],
                    'cliente'          => $e['cliente'],
                    'tn_entregadas'    => $e['tn_entregadas'],
                    'precio_venta'     => $precioVentaBs,
                    'fecha_entrega'    => $e['fecha_entrega'],
                    'total_ventas'     => $totalVentas,
                    'precio_compra'    => $costoUnitario,
                    'importe_compra'   => $importeCompra,
                    'utilidad_bruta'   => $utilidadBruta,
                    'it_3'             => $it,
                    'comision_1_3'     => $comision1,
                    'comision_2_zpl'   => $comision2,
                    'costo_adicional'  => $costoAdicional,
                    'utilidad_neta'    => $utilidadNeta,
                    'tipo_cambio_contrato' => $tcContrato,
                    'es_subtotal'      => false,
                ]);
            }

            // Una fila por cada cobro individual al cliente — separado de la
            // fila de la entrega para que cada cobro conserve su propio tipo
            // de cambio y no se mezclen varios TC en una sola celda sumada.
            foreach ($cobrosCliente as $cob) {
                $sumaMontoCobrado += $cob['monto_bs'];
                $contratosExcelData->push(['moneda' => 'BOB'] + $filaBase + [
                    'placa'            => '',
                    'cliente'          => 'COBRO CLIENTE: ' . $cob['cliente'],
                    'tn_entregadas'    => '',
                    'precio_venta'     => '',
                    'total_ventas'     => '',
                    'precio_compra'    => '',
                    'importe_compra'   => '',
                    'utilidad_bruta'   => '',
                    'it_3'             => '',
                    'comision_1_3'     => '',
                    'comision_2_zpl'   => '',
                    'costo_adicional'  => '',
                    'utilidad_neta'    => '',
                    'monto_cobrado_cliente' => $cob['monto_bs'],
                    'tipo_cambio_cobro'     => $cob['tipo_cambio'],
                    'codigo_cobro_cliente'  => $cob['codigo'],
                    'fecha_cobro_cliente'   => $cob['fecha'],
                    'es_subtotal'      => false,
                ]);
            }

            // Una fila por cada pago individual al proveedor, entre las
            // entregas y el subtotal — antes iban comprimidos en una sola
            // celda del subtotal (implode), ahora cada pago es su propia fila.
            foreach ($c->pagosProveedor as $p) {
                if ($p->deleted_at) continue;
                $tcPago = $p->moneda_pago === 'BOB' ? 1 : ((float) $p->tipo_cambio ?: 1);
                $montoPagoBs = $p->moneda_pago === 'BOB' ? (float) $p->monto : round((float) $p->monto * $tcPago, 2);
                $contratosExcelData->push(['moneda' => 'BOB'] + $filaBase + [
                    'placa'            => '',
                    'cliente'          => 'PAGO PROVEEDOR',
                    'tn_entregadas'    => '',
                    'precio_venta'     => '',
                    'total_ventas'     => '',
                    'precio_compra'    => '',
                    'importe_compra'   => '',
                    'utilidad_bruta'   => '',
                    'it_3'             => '',
                    'comision_1_3'     => '',
                    'comision_2_zpl'   => '',
                    'costo_adicional'  => '',
                    'utilidad_neta'    => '',
                    'monto_pagado_proveedor' => $montoPagoBs,
                    'tipo_cambio_pago' => $tcPago,
                    'codigo_pago_proveedor'  => $p->codigo_seguimiento ?? '',
                    'fecha_pago_proveedor'   => $p->fecha_pago ? $p->fecha_pago->format('Y-m-d') : '',
                    'es_subtotal'      => false,
                    'es_pago_proveedor'=> true,
                ]);
            }

            // Una fila por cada pago de flete individual (camión/conductor) —
            // mismo criterio que Pago Proveedor: cada transferencia real es su
            // propia fila, convertida a Bs con su propio tipo de cambio.
            $montoPagadoFlete = 0;
            foreach ($c->contratoCamiones as $cc) {
                foreach ($cc->pagos as $pf) {
                    if ($pf->deleted_at) continue;
                    $tcFlete = $pf->moneda_pago === 'BOB' ? 1 : ((float) $pf->tipo_cambio ?: 1);
                    $montoFleteBs = $pf->moneda_pago === 'BOB' ? (float) $pf->monto : round((float) $pf->monto * $tcFlete, 2);
                    $montoPagadoFlete += $montoFleteBs;
                    $contratosExcelData->push(['moneda' => 'BOB'] + $filaBase + [
                        'placa'            => $cc->camion->placa ?? '',
                        'cliente'          => 'PAGO FLETE',
                        'tn_entregadas'    => '',
                        'precio_venta'     => '',
                        'total_ventas'     => '',
                        'precio_compra'    => '',
                        'importe_compra'   => '',
                        'utilidad_bruta'   => '',
                        'it_3'             => '',
                        'comision_1_3'     => '',
                        'comision_2_zpl'   => '',
                        'costo_adicional'  => '',
                        'utilidad_neta'    => '',
                        'monto_pagado_flete' => $montoFleteBs,
                        'tipo_cambio_flete'  => $tcFlete,
                        'codigo_pago_flete'  => $pf->codigo_seguimiento ?? '',
                        'fecha_pago_flete'   => $pf->fecha_pago ? $pf->fecha_pago->format('Y-m-d') : '',
                        // El registro/edición debe ser del propio pago de flete, no
                        // del contrato al que está asociado (que ya viene en $filaBase).
                        'fecha_registro'   => $pf->created_at?->format('d/m/Y H:i') ?? '',
                        'registrado_por'   => $pf->usuarioCreador->name ?? '',
                        'fecha_edicion'    => $pf->updated_at && !$pf->updated_at->equalTo($pf->created_at) ? $pf->updated_at->format('d/m/Y H:i') : '',
                        'editado_por'      => $pf->updated_at && !$pf->updated_at->equalTo($pf->created_at) ? ($pf->usuarioActualizador->name ?? '') : '',
                        'es_subtotal'      => false,
                        'es_pago_flete'    => true,
                    ]);
                }
            }

            // Una fila por cada gasto extra PAGADO asociado a este contrato —
            // solo los pagados cuentan como movimiento real en tesorería,
            // igual criterio que el bloque de gastos generales al final.
            foreach ($c->gastosExtras as $ge) {
                if ($ge->estado !== 'PAGADO') continue;
                $sumaGastoExtra += (float) $ge->monto_bolivianos;
                $contratosExcelData->push(['moneda' => 'BOB'] + $filaBase + [
                    'placa'            => '',
                    'cliente'          => 'GASTO EXTRA: ' . $ge->categoria,
                    'tn_entregadas'    => '',
                    'precio_venta'     => '',
                    'total_ventas'     => '',
                    'precio_compra'    => '',
                    'importe_compra'   => '',
                    'utilidad_bruta'   => '',
                    'it_3'             => '',
                    'comision_1_3'     => '',
                    'comision_2_zpl'   => '',
                    'costo_adicional'  => '',
                    'utilidad_neta'    => '',
                    'gasto_extra'      => (float) $ge->monto_bolivianos,
                    'tipo_cambio_gasto_extra' => $ge->moneda === 'BOB' ? 1 : ((float) $ge->tipo_cambio ?: 1),
                    'codigo_gasto_extra' => $ge->codigo_seguimiento ?? '',
                    // El registro/edición debe ser del propio gasto extra, no
                    // del contrato al que está asociado (que ya viene en $filaBase).
                    'fecha_registro'   => $ge->created_at?->format('d/m/Y H:i') ?? '',
                    'registrado_por'   => $ge->usuarioCreador->name ?? '',
                    'fecha_edicion'    => $ge->updated_at && !$ge->updated_at->equalTo($ge->created_at) ? $ge->updated_at->format('d/m/Y H:i') : '',
                    'editado_por'      => $ge->updated_at && !$ge->updated_at->equalTo($ge->created_at) ? ($ge->usuarioActualizador->name ?? '') : '',
                    'es_subtotal'      => false,
                ]);
            }

            // Fila de subtotal del contrato (siempre, tenga o no entregas).
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
                    'precio_compra'    => '',
                    'importe_compra'   => round($sumaCompras, 2),
                    'utilidad_bruta'   => round($sumaVentas - $sumaCompras, 2),
                    'it_3'             => round($sumaIt, 2),
                    'comision_1_3'     => round($sumaCom1, 2),
                    'comision_2_zpl'   => round($sumaCom2, 2),
                    'costo_adicional'  => round($sumaCostoAdicional, 2),
                    'utilidad_neta'    => round($sumaUtilNeta - $sumaGastoExtra, 2),
                    'estado_envios'    => $c->envios_cerrados ? 'Envíos cerrados' : 'Envíos abiertos',
                    'monto_cobrado_cliente'   => round($sumaMontoCobrado, 2),
                    'monto_pagado_proveedor'  => round($montoPagadoProveedor, 2),
                    'codigo_pago_proveedor'   => implode(', ', $codigosPagoProveedor),
                    'fecha_pago_proveedor'    => implode(', ', $fechasPagoProveedor),
                    'monto_pagado_flete'      => round($montoPagadoFlete, 2),
                    'gasto_extra'      => round($sumaGastoExtra, 2),
                    'tipo_cambio_contrato' => $tcContrato,
                    'es_subtotal'      => true,
                ]);
            } else {
                // Sin entregas registradas todavía: primero una fila normal con
                // los datos del contrato (Tipo/Proveedor visibles), y debajo su
                // SUBTOTAL en 0, igual que los contratos que sí tuvieron entregas.
                $contratosExcelData->push($filaBase + [
                    'placa'            => '',
                    'cliente'          => '',
                    'tn_entregadas'    => 0,
                    'precio_venta'     => '',
                    'fecha_entrega'    => '',
                    'total_ventas'     => '',
                    'precio_compra'    => '',
                    'importe_compra'   => '',
                    'utilidad_bruta'   => '',
                    'it_3'             => '',
                    'comision_1_3'     => '',
                    'comision_2_zpl'   => '',
                    'costo_adicional'  => '',
                    'utilidad_neta'    => '',
                    'monto_cobrado_cliente' => '',
                    'codigo_cobro_cliente'  => '',
                    'fecha_cobro_cliente'   => '',
                    'es_subtotal'      => false,
                ]);
                $contratosExcelData->push($filaBase + [
                    'placa'            => '',
                    'cliente'          => 'SUBTOTAL ' . $c->numero_contrato,
                    'tn_entregadas'    => 0,
                    'precio_venta'     => '',
                    'total_ventas'     => 0,
                    'precio_compra'    => '',
                    'importe_compra'   => 0,
                    'utilidad_bruta'   => 0,
                    'it_3'             => 0,
                    'comision_1_3'     => 0,
                    'comision_2_zpl'   => 0,
                    'costo_adicional'  => 0,
                    'utilidad_neta'    => round(0 - $sumaGastoExtra, 2),
                    'estado_envios'    => $c->envios_cerrados ? 'Envíos cerrados' : 'Envíos abiertos - SIN ENVIOS',
                    'monto_cobrado_cliente'   => 0,
                    'monto_pagado_proveedor'  => round($montoPagadoProveedor, 2),
                    'codigo_pago_proveedor'   => implode(', ', $codigosPagoProveedor),
                    'fecha_pago_proveedor'    => implode(', ', $fechasPagoProveedor),
                    'monto_pagado_flete'      => round($montoPagadoFlete, 2),
                    'gasto_extra'      => round($sumaGastoExtra, 2),
                    'tipo_cambio_contrato' => $tcContrato,
                    'es_subtotal'      => true,
                ]);
            }
        }

        $contratosExcelData = $contratosExcelData->values();

        // Gastos extra generales (sin contrato), solo PAGADOS — es el único
        // caso que representa un movimiento real ya ocurrido en tesorería.
        // Se agrupan por categoría con su propio subtotal, al final del Excel.
        $gastosExtraGeneralesPorCategoria = GastoExtra::whereNull('contrato_id')
            ->whereNull('deleted_at')
            ->where('estado', 'PAGADO')
            ->with(['usuarioCreador', 'usuarioActualizador'])
            ->orderBy('categoria')
            ->orderBy('fecha')
            ->get()
            ->groupBy('categoria')
            ->map(function ($grupo) {
                return [
                    'categoria' => $grupo->first()->categoria,
                    'items' => $grupo->map(fn ($ge) => [
                        'fecha'    => $ge->fecha ? $ge->fecha->format('d/m/Y') : '',
                        'concepto' => $ge->concepto,
                        'monto'    => (float) $ge->monto_bolivianos,
                        'codigo'   => $ge->codigo_seguimiento ?? '',
                        'fecha_registro' => $ge->created_at?->format('d/m/Y H:i') ?? '',
                        'registrado_por' => $ge->usuarioCreador->name ?? '',
                        'fecha_edicion'  => $ge->updated_at && !$ge->updated_at->equalTo($ge->created_at) ? $ge->updated_at->format('d/m/Y H:i') : '',
                        'editado_por'    => $ge->updated_at && !$ge->updated_at->equalTo($ge->created_at) ? ($ge->usuarioActualizador->name ?? '') : '',
                    ])->values(),
                    'subtotal' => round($grupo->sum('monto_bolivianos'), 2),
                ];
            })
            ->values();

        return view('contratos.index', compact('contratos', 'clientes', 'proveedores', 'numeroSiguiente', 'idempotencyToken', 'contratosExcelData', 'gastosExtraGeneralesPorCategoria'));
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
