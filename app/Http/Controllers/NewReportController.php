<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoCamion;
use App\Models\Tramo;
use App\Models\Movimiento;
use App\Models\CuentaEmpresa;
use App\Exports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;


class NewReportController extends Controller
{
    private const CATEGORIAS_MOVIMIENTO = [
        'anticipo_cliente' => 'Anticipo de Cliente',
        'pago_cliente' => 'Pago de Cliente',
        'pago_proveedor' => 'Pago a Proveedor',
        'pago_camion' => 'Pago a Camión',
        'gasto_extra' => 'Gasto Extra',
        'pago_sueldo' => 'Pago de Sueldo',
        'prestamo_otorgado' => 'Préstamo Otorgado',
        'prestamo_recibido' => 'Préstamo Recibido',
        'devolucion_prestamo' => 'Devolución Préstamo',
        'otro' => 'Otro',
    ];
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'deudas_proveedores');
        if (!in_array($tab, ['deudas_proveedores', 'deudas_fletes', 'cuentas_cobrar', 'flujo_caja'])) {
            $tab = 'deudas_proveedores';
        }
        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?? now()->format('Y-m-d');
        $proveedores = Proveedor::whereNull('deleted_at')->whereHas('contratos')->orderBy('nombre')->get();
        $clientes = Cliente::whereNull('deleted_at')->orderBy('nombre')->get();
        $contratos = Contrato::whereNull('deleted_at')->get();
        $estadoContratos = $contratos->pluck('estado')->filter()->unique()->values();
        $cuentasEmpresa = CuentaEmpresa::whereNull('deleted_at')->where('activo', true)->orderBy('nombre_cuenta')->get();
        $data = compact('tab','fechaInicio','fechaFin','proveedores','clientes','contratos','estadoContratos','cuentasEmpresa');
        $data['categoriasMovimiento'] = self::CATEGORIAS_MOVIMIENTO;
        $tieneFiltros = count($request->except('tab')) > 0;
        $data['reporte'] = $tieneFiltros ? $this->construirReporte($tab, $request, $fechaInicio, $fechaFin) : $this->reporteVacio($tab);
        return view('reports.index', $data);
    }
    private function reporteVacio(string $tab): array
    {
        switch ($tab) {
            case 'deudas_fletes':
                return ['items' => collect(), 'totales' => ['monto_acordado' => 0, 'monto_neto' => 0, 'pagado' => 0, 'pendiente' => 0,],];
            case 'cuentas_cobrar':
                return ['items'    => collect(),'sinCobro' => collect(),'totales'  => ['facturado' => 0,'cobrado' => 0,'pendiente' => 0,],];
            case 'flujo_caja':
                return ['items' => collect(),'porCategoria' => collect(),'totales' => ['ingresos' => 0,'egresos' => 0,'saldoNeto' => 0,],];
            default:
                return ['items' => collect(),'totales' => ['monto_total' => 0,'pagado' => 0,'pendiente' => 0,],];
        }
    }

    public function export(Request $request)
    {
        $tab = $request->get('tab', 'deudas_proveedores');
        if (!in_array($tab, ['deudas_proveedores', 'deudas_fletes', 'cuentas_cobrar', 'flujo_caja'])) {
            $tab = 'deudas_proveedores';
        }
        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin    = $request->fecha_fin ?? now()->format('Y-m-d');
        $reporte = $this->construirReporte($tab, $request, $fechaInicio, $fechaFin);
        $titulo  = $this->tituloReporte($tab);
        $filtros = $this->filtrosLegibles($tab, $request, $fechaInicio, $fechaFin);
        $totales = $this->totalesLegibles($tab, $reporte);
        [$columnas, $filas, $tiposColumna] = $this->prepararFilasExport($tab, $reporte);
        $nombreArchivo = 'reporte_' . $tab . '_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new ArrayExport($titulo, $filtros, $columnas, $filas, $totales, $tiposColumna),$nombreArchivo);
    }
    private function tituloReporte(string $tab): string
    {
        return match ($tab) {
            'deudas_fletes' => 'Reporte de Deudas por Fletes',
            'cuentas_cobrar' => 'Reporte de Cuentas por Cobrar',
            'flujo_caja' => 'Reporte de Flujo de Caja',
            default => 'Reporte de Deudas a Proveedores',};
    }
    private function filtrosLegibles(string $tab, Request $request, string $fechaInicio, string $fechaFin): array
    {
        $filtros = ['Fecha Inicio' => $fechaInicio,'Fecha Fin' => $fechaFin,];
        if (in_array($tab, ['deudas_proveedores', 'deudas_fletes']) && $request->filled('proveedor_id')) {
            $filtros['Proveedor'] = optional(Proveedor::find($request->proveedor_id))->nombre ?? $request->proveedor_id;
        }
        if (in_array($tab, ['deudas_proveedores', 'deudas_fletes', 'cuentas_cobrar']) && $request->filled('contrato_id')) {
            $filtros['Contrato'] = optional(Contrato::find($request->contrato_id))->numero_contrato ?? $request->contrato_id;
        }
        if ($tab === 'deudas_proveedores' && $request->filled('estado_contrato')) {
            $filtros['Estado Contrato'] = ucfirst($request->estado_contrato);
        }
        if (in_array($tab, ['deudas_proveedores', 'deudas_fletes']) && $request->filled('estado_deuda')) {
            $filtros['Estado Deuda'] = ucfirst($request->estado_deuda);
        }
        if ($tab === 'cuentas_cobrar') {
            if ($request->filled('cliente_id')) {
                $filtros['Cliente'] = optional(Cliente::find($request->cliente_id))->nombre ?? $request->cliente_id;
            }
            if ($request->filled('estado_entrega')) {
                $filtros['Estado Entrega'] = $request->estado_entrega;
            }
            if ($request->filled('estado_cobro')) {
                $filtros['Estado Cobro'] = ucfirst($request->estado_cobro);
            }
        }
        if ($tab === 'flujo_caja') {
            if ($request->filled('cuenta_empresa_id')) {
                $filtros['Cuenta'] = optional(CuentaEmpresa::find($request->cuenta_empresa_id))->nombre_cuenta ?? $request->cuenta_empresa_id;
            }
            if ($request->filled('tipo_movimiento')) {
                $filtros['Tipo'] = ucfirst($request->tipo_movimiento);
            }
            if ($request->filled('categoria')) {
                $filtros['Categoría'] = self::CATEGORIAS_MOVIMIENTO[$request->categoria] ?? $request->categoria;
            }
        }
        return $filtros;
    }
    private function totalesLegibles(string $tab, array $reporte): array
    {
        return match ($tab) {
            'deudas_fletes' => ['Monto Acordado (Neto)' => $reporte['totales']['monto_neto'],'Total Pagado' => $reporte['totales']['pagado'],'Saldo Pendiente' => $reporte['totales']['pendiente'],],
            'cuentas_cobrar' => ['Total Facturado' => $reporte['totales']['facturado'],'Total Cobrado' => $reporte['totales']['cobrado'],'Saldo Pendiente' => $reporte['totales']['pendiente'],],
            'flujo_caja' => ['Total Ingresos' => $reporte['totales']['ingresos'],'Total Egresos' => $reporte['totales']['egresos'],'Saldo Neto' => $reporte['totales']['saldoNeto'],],
            default => ['Monto Total Contratos' => $reporte['totales']['monto_total'],'Total Pagado' => $reporte['totales']['pagado'],'Saldo Pendiente' => $reporte['totales']['pendiente'],],};
    }

    private function construirReporte(string $tab, Request $request, string $fechaInicio, string $fechaFin): array
    {
        switch ($tab) {
            case 'deudas_fletes':
                return $this->reporteDeudasFletes($request);
            case 'cuentas_cobrar':
                return $this->reporteCuentasCobrar($request);
            case 'flujo_caja':
                return $this->reporteFlujoCaja($request, $fechaInicio, $fechaFin);
            default:
                return $this->reporteDeudasProveedores($request);
        }
    }

    private function reporteDeudasProveedores(Request $request): array
    {
        $query = Contrato::with(['proveedor', 'pagosProveedor'])->whereNull('deleted_at')->whereNotNull('proveedor_id');
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        if ($request->filled('contrato_id')) {
            $query->where('id', $request->contrato_id);
        }
        if ($request->filled('estado_contrato')) {
            $query->where('estado', $request->estado_contrato);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_inicio', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_inicio', '<=', $request->fecha_fin);
        }

        $contratos = $query->get();
        if ($request->filled('estado_deuda')) {
            $contratos = $contratos->filter(function ($c) use ($request) {
                $tieneDeuda = $c->saldo_pendiente_proveedor > 0;
                return $request->estado_deuda === 'pendiente' ? $tieneDeuda : !$tieneDeuda;})->values();
        }
        return [
            'items' => $contratos,
            'totales' => ['monto_total' => $contratos->sum('monto_total'),'pagado' => $contratos->sum('total_pagado_proveedor'),'pendiente' => $contratos->sum('saldo_pendiente_proveedor'),],
        ];
    }
    private function reporteDeudasFletes(Request $request): array
    {
        $query = ContratoCamion::with(['contrato.proveedor', 'camion', 'pagos'])->where('activo', true);
        if ($request->filled('proveedor_id')) {
            $query->whereHas('contrato', fn ($q) => $q->where('proveedor_id', $request->proveedor_id));
        }
        if ($request->filled('contrato_id')) {
            $query->where('contrato_id', $request->contrato_id);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_asignacion', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_asignacion', '<=', $request->fecha_fin);
        }

        $items = $query->get();

        if ($request->filled('estado_deuda')) {
            $items = $items->filter(function ($cc) use ($request) {
                $tieneDeuda = $cc->saldo_pendiente > 0;
                return $request->estado_deuda === 'pendiente' ? $tieneDeuda : !$tieneDeuda;
            })->values();
        }

        return [
            'items' => $items,
            'totales' => [
                'monto_acordado' => $items->sum('monto_acordado'),'monto_neto' => $items->sum('monto_neto'),'pagado' => $items->sum('total_pagado'),'pendiente'      => $items->sum('saldo_pendiente'),
            ],
        ];
    }

    private function reporteCuentasCobrar(Request $request): array
    {
        $query = Tramo::with(['cliente', 'contratoCamion.contrato', 'pagosCliente'])->where('activo', true)->whereNotNull('cliente_id');
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled('contrato_id')) {
            $query->whereHas('contratoCamion', fn ($q) => $q->where('contrato_id', $request->contrato_id));
        }
        if ($request->filled('estado_entrega')) {
            $query->where('estado', $request->estado_entrega);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_llegada', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_llegada', '<=', $request->fecha_fin);
        }

        $tramos = $query->get();

        if ($request->filled('estado_cobro')) {
            $tramos = $tramos->filter(function ($t) use ($request) {
                $tieneDeuda = $t->saldo_cliente > 0;
                return $request->estado_cobro === 'pendiente' ? $tieneDeuda : !$tieneDeuda;
            })->values();
        }

        $sinCobro = $tramos->filter(fn ($t) => $t->estado === 'Entregado' && $t->total_cobrado_cliente <= 0)->values();
        return ['items' => $tramos,'sinCobro' => $sinCobro,'totales' => ['facturado' => $tramos->sum('monto_deuda_cliente'),'cobrado'   => $tramos->sum('total_cobrado_cliente'),'pendiente' => $tramos->sum('saldo_cliente'),],];
    }

    private function reporteFlujoCaja(Request $request, string $fechaInicio, string $fechaFin): array
    {
        $query = Movimiento::with('cuentaEmpresa')->whereNull('deleted_at')->whereDate('fecha', '>=', $fechaInicio)->whereDate('fecha', '<=', $fechaFin);

        if ($request->filled('cuenta_empresa_id')) {
            $query->where('cuenta_empresa_id', $request->cuenta_empresa_id);
        }
        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo', $request->tipo_movimiento);
        }
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        $movimientos = $query->orderByDesc('fecha')->get();
        $ingresos = (float) $movimientos->where('tipo', 'ingreso')->sum('monto_bolivianos');
        $egresos  = (float) $movimientos->where('tipo', 'egreso')->sum('monto_bolivianos');
        $porCategoria = $movimientos->groupBy('categoria')->map(function ($grupo) {
            $cat = $grupo->first()->categoria;
            return ['categoria' => $cat,'label' => Movimiento::categoriaLabel($cat),'ingreso'   => (float) $grupo->where('tipo', 'ingreso')->sum('monto_bolivianos'),'egreso'    => (float) $grupo->where('tipo', 'egreso')->sum('monto_bolivianos'),];
        })->values();
        $egresosPorContrato = $movimientos->whereIn('categoria', ['pago_proveedor', 'pago_camion', 'gasto_extra'])
            ->groupBy(function ($m) {
                return optional($m->origen)->contrato_id ?? optional(optional($m->origen)->contratoCamion)->contrato_id ?? 'sin_contrato';});
        return ['items' => $movimientos, 'porCategoria' => $porCategoria,
            'totales' => ['ingresos' => $ingresos,'egresos' => $egresos,'saldoNeto' => $ingresos - $egresos,],
        ];
    }
    private function prepararFilasExport(string $tab, array $reporte): array
    {
        switch ($tab) {
            case 'deudas_fletes':
                return [
                    ['Contrato', 'Proveedor', 'Camión', 'Monto Neto', 'Pagado', 'Pendiente', 'Moneda'],
                    $reporte['items']->map(fn ($cc) => [
                        $cc->contrato->numero_contrato ?? '—',
                        $cc->contrato->proveedor->nombre ?? '—',
                        $cc->camion->placa ?? '—',
                        (float) $cc->monto_neto,
                        (float) $cc->total_pagado,
                        (float) $cc->saldo_pendiente,
                        $cc->moneda_flete,
                    ])->toArray(),
                    ['texto', 'texto', 'texto', 'numero', 'numero', 'numero', 'centro'],
                ];

            case 'cuentas_cobrar':
                return [
                    ['Contrato', 'Cliente', 'Estado Entrega', 'Peso Llegada (Ton)', 'Facturado', 'Cobrado', 'Pendiente', 'Moneda'],
                    $reporte['items']->map(fn ($t) => [
                        $t->contratoCamion->contrato->numero_contrato ?? '—',
                        $t->cliente->nombre ?? '—',
                        $t->estado,
                        (float) $t->peso_llegada,
                        (float) $t->monto_deuda_cliente,
                        (float) $t->total_cobrado_cliente,
                        (float) $t->saldo_cliente,
                        $t->moneda_venta,
                    ])->toArray(),
                    ['texto', 'texto', 'centro', 'numero', 'numero', 'numero', 'numero', 'centro'],
                ];

            case 'flujo_caja':
                return [
                    ['Fecha', 'Cuenta', 'Tipo', 'Categoría', 'Concepto', 'Monto (Bs)'],
                    $reporte['items']->map(fn ($m) => [
                        $m->fecha->format('d/m/Y'),
                        $m->cuentaEmpresa->nombre_cuenta ?? '—',
                        ucfirst($m->tipo),
                        Movimiento::categoriaLabel($m->categoria),
                        $m->concepto,
                        (float) $m->monto_bolivianos,
                    ])->toArray(),
                    ['centro', 'texto', 'centro', 'texto', 'texto', 'numero'],
                ];

            default: // deudas_proveedores
                return [
                    ['Fecha', 'Contrato', 'Proveedor', 'Estado', 'Toneladas Recibidas', 'Monto Total', 'Pagado', 'Pendiente', 'Moneda'],
                    $reporte['items']->map(fn ($c) => [
                        $c->fecha_inicio?->format('d/m/Y') ?? '—',
                        $c->numero_contrato,
                        $c->proveedor->nombre ?? '—',
                        ucfirst($c->estado),
                        (float) $c->toneladas_entregadas,
                        (float) $c->monto_total,
                        (float) $c->total_pagado_proveedor,
                        (float) $c->saldo_pendiente_proveedor,
                        $c->moneda,
                    ])->toArray(),
                    ['centro', 'texto', 'texto', 'centro', 'numero', 'numero', 'numero', 'numero', 'centro'],
                ];
        }
    }
}