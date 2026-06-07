<?php

namespace App\Exports;
use App\Models\Contrato;
use App\Models\ContratoCamion;
use App\Models\CuentaEmpresa;
use App\Models\GastoExtra;
use App\Models\Movimiento;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Tramo;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CapitalUtilidadExport implements WithMultipleSheets
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin,
        public string $cuentaId = 'todas'
    ) {}

    public function sheets(): array
    {
        return [
            new ResumenEjecutivoSheet($this->fechaInicio, $this->fechaFin, $this->cuentaId),
            new ComprasContratosSheet($this->fechaInicio, $this->fechaFin),
            new LogisticaTramosSheet($this->fechaInicio, $this->fechaFin),
            new PagosProveedoresSheet($this->fechaInicio, $this->fechaFin, $this->cuentaId),
            new CobrosClientesSheet($this->fechaInicio, $this->fechaFin, $this->cuentaId),
            new GastosExtrasSheet($this->fechaInicio, $this->fechaFin),
            new TesoreriaCuentasSheet($this->fechaInicio, $this->fechaFin, $this->cuentaId),
        ];
    }
}


class BaseSheetStyle
{
    public static function aplicar(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2F4050'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B7B7B7'],
                ],
            ],
        ]);

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);
    }

    public static function resumen(Worksheet $sheet): void
    {
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle('A3:D4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAF2F8'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B7B7B7'],
                ],
            ],
        ]);

        $sheet->getStyle('A6:B6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2F4050'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A6:B{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B7B7B7'],
                ],
            ],
        ]);

        $sheet->getStyle("B7:B{$highestRow}")->getNumberFormat()->setFormatCode('"Bs" #,##0.00');
        $sheet->getStyle('A7:A19')->getFont()->setBold(true);
        $sheet->getStyle('A16:B19')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F4F6F7'],
            ],
            'font' => [
                'bold' => true,
            ],
        ]);
    }
}

class ResumenEjecutivoSheet implements FromArray, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin,
        public string $cuentaId
    ) {}

    public function title(): string
    {
        return 'Resumen';
    }

   public function array(): array
    {
        $cuentasQuery = CuentaEmpresa::whereNull('deleted_at');

    if ($this->cuentaId !== 'todas') {
        $cuentasQuery->where('id', $this->cuentaId);
    }
    $cuentas = $cuentasQuery->get();
    $movQuery = Movimiento::whereNull('deleted_at')->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
    if ($this->cuentaId !== 'todas') {
        $movQuery->where('cuenta_empresa_id', $this->cuentaId);
    }
    $movimientos = $movQuery->get();
    $capitalInicial = $cuentas->sum('saldo_inicial');
    $saldoActual = $cuentas->sum('saldo_actual');
    $ventasClientes = $movimientos->where('tipo', 'ingreso')->whereIn('categoria', ['pago_cliente', 'anticipo_cliente'])->sum('monto_bolivianos');
    $otrosIngresos = $movimientos->where('tipo', 'ingreso')->whereNotIn('categoria', ['pago_cliente', 'anticipo_cliente'])->sum('monto_bolivianos');
    $pagosProveedores = $movimientos->where('tipo', 'egreso')->where('categoria', 'pago_proveedor')->sum('monto_bolivianos');
    $pagosCamiones = $movimientos->where('tipo', 'egreso')->where('categoria', 'pago_camion')->sum('monto_bolivianos');
    $gastosExtras = $movimientos->where('tipo', 'egreso')->where('categoria', 'gasto_extra')->sum('monto_bolivianos');
    $otrosEgresos = $movimientos->where('tipo', 'egreso')->whereNotIn('categoria', ['pago_proveedor', 'pago_camion', 'gasto_extra'])->sum('monto_bolivianos');
    $totalIngresos = $ventasClientes + $otrosIngresos;
    $totalEgresos = $pagosProveedores + $pagosCamiones + $gastosExtras + $otrosEgresos;

    $capitalFinalCalculado = $capitalInicial + $totalIngresos - $totalEgresos;
    $utilidadOperativa = $ventasClientes - $pagosProveedores - $pagosCamiones - $gastosExtras;
    $utilidadNeta = $totalIngresos - $totalEgresos;

    return [
        ['REPORTE FINANCIERO DE CAPITAL Y UTILIDAD', '', '', ''],
        [],
        ['FECHA INICIO', $this->fechaInicio, 'FECHA FIN', $this->fechaFin],
        ['Cuenta', $this->cuentaId === 'todas' ? 'Todas las cuentas' : $this->cuentaId, 'Generado', now()->format('Y-m-d H:i')],
        [],
        ['CONCEPTO', 'MONTO BS'],
        ['Capital inicial', $capitalInicial],
        ['Ventas / cobros clientes', $ventasClientes],
        ['Otros ingresos', $otrosIngresos],
        ['Total ingresos', $totalIngresos],
        ['Pagos a proveedores', $pagosProveedores],
        ['Pagos a camiones', $pagosCamiones],
        ['Gastos extras', $gastosExtras],
        ['Otros egresos', $otrosEgresos],
        ['Total egresos', $totalEgresos],
        ['Capital final calculado', $capitalFinalCalculado],
        ['Saldo actual en tesorería', $saldoActual],
        ['Utilidad operativa', $utilidadOperativa],
        ['Utilidad neta', $utilidadNeta],
    ];
}

    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::resumen($sheet);
    }
}

class ComprasContratosSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin
    ) {}

    public function title(): string
    {
        return 'Compras Contratos';
    }

    public function headings(): array
    {
        return [
            'Contrato',
            'Fecha Inicio',
            'Proveedor',
            'Cliente',
            'Tipo Contrato',
            'Toneladas Contrato',
            'Monto Total',
            'Moneda',
            'Pagado Proveedor',
            'Saldo Proveedor',
            'Cantidad Camiones',
            'Estado',
        ];
    }

    public function array(): array
    {
        return Contrato::with(['proveedor', 'cliente', 'pagosProveedor', 'contratoCamiones'])->whereNull('deleted_at')->whereBetween('fecha_inicio', [$this->fechaInicio, $this->fechaFin])->orderBy('fecha_inicio')->get()->map(function ($c) {
                $pagado = $c->pagosProveedor->sum(function ($p) {
                    $tc = $p->tipo_cambio ?: 1;
                    return $p->moneda_pago === 'BOB' ? $p->monto : $p->monto * $tc;
                });
                $montoTotalBob = $c->moneda === 'BOB' ? $c->monto_total : $c->monto_total * 6.96;
                return [$c->numero_contrato, optional($c->fecha_inicio)->format('Y-m-d'),$c->proveedor->nombre ?? '-',$c->cliente->nombre ?? '-',$c->tipo_contrato,$c->toneladas_contrato,$montoTotalBob,$c->moneda,$pagado,$montoTotalBob - $pagado,$c->contratoCamiones->count(),$c->estado,];})->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::aplicar($sheet);
    }
}

class LogisticaTramosSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin
    ) {}

    public function title(): string
    {
        return 'Logistica';
    }

    public function headings(): array
    {
        return ['Contrato','Proveedor','Cliente','Camión','Chofer','Origen','Destino','Peso Declarado','Peso Salida','Peso Llegada','Diferencia','Estado','Fecha Salida','Fecha Llegada',];
    }

    public function array(): array
    {
        return Tramo::with(['contratoCamion.contrato.proveedor','contratoCamion.contrato.cliente','camion','conductor',])->whereNull('deleted_at')->whereBetween('fecha_salida', [$this->fechaInicio, $this->fechaFin])->orderBy('fecha_salida')->get()
            ->map(function ($t) {
                $contrato = $t->contratoCamion?->contrato;
                $diferencia = ($t->peso_llegada ?? 0) - ($t->peso_salida ?? 0);
                return [
                    $contrato->numero_contrato ?? '-',
                    $contrato->proveedor->nombre ?? '-',
                    $contrato->cliente->nombre ?? '-',
                    $t->camion->placa ?? '-',
                    $t->conductor->nombre_completo ?? '-',
                    $t->origen,
                    $t->destino,
                    $t->peso_declarado,
                    $t->peso_salida,
                    $t->peso_llegada,
                    $diferencia,
                    $t->estado,
                    optional($t->fecha_salida)->format('Y-m-d'),
                    optional($t->fecha_llegada)->format('Y-m-d'),
                ];
            })->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::aplicar($sheet);
    }
}

class PagosProveedoresSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin,
        public string $cuentaId
    ) {}

    public function title(): string
    {
        return 'Pagos Proveedores';
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Contrato',
            'Proveedor',
            'Tipo Pago',
            'Método Pago',
            'Cuenta Origen',
            'Monto',
            'Moneda',
            'TC',
            'Monto Bs',
            'Código',
            'Observaciones',
        ];
    }

    public function array(): array
    {
        $query = PagoProveedor::with(['contrato.proveedor', 'cuentaOrigen'])->whereNull('deleted_at')->whereBetween('fecha_pago', [$this->fechaInicio, $this->fechaFin]);
        if ($this->cuentaId !== 'todas') {
            $query->where('cuenta_origen_id', $this->cuentaId);
        }
        return $query->orderBy('fecha_pago')->get()
            ->map(function ($p) {
                $tc = $p->tipo_cambio ?: 1;
                return [
                    optional($p->fecha_pago)->format('Y-m-d'),
                    $p->contrato->numero_contrato ?? '-',
                    $p->contrato->proveedor->nombre ?? '-',
                    $p->tipo_pago,
                    $p->metodo_pago,
                    $p->cuentaOrigen->nombre_cuenta ?? '-',
                    $p->monto,
                    $p->moneda_pago,
                    $tc,
                    $p->moneda_pago === 'BOB' ? $p->monto : $p->monto * $tc,
                    $p->codigo_seguimiento,
                    $p->observaciones,
                ];
            })->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::aplicar($sheet);
    }
}

class CobrosClientesSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin,
        public string $cuentaId
    ) {}

    public function title(): string
    {
        return 'Cobros Clientes';
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Contrato',
            'Cliente',
            'Tipo Pago',
            'Método Pago',
            'Cuenta Destino',
            'Monto',
            'Moneda',
            'TC',
            'Monto Bs',
            'Código',
            'Observaciones',
        ];
    }

    public function array(): array
    {
        $query = PagoCliente::with([
                'tramo.contratoCamion.contrato.cliente',
                'cuentaDestino',
            ])->whereNull('deleted_at')->whereBetween('fecha_pago', [$this->fechaInicio, $this->fechaFin]);
        if ($this->cuentaId !== 'todas') {
            $query->where('cuenta_destino_id', $this->cuentaId);
        }

        return $query->orderBy('fecha_pago')->get()
            ->map(function ($p) {
                $contrato = $p->tramo?->contratoCamion?->contrato;
                $tc = $p->tipo_cambio ?: 1;
                return [
                    optional($p->fecha_pago)->format('Y-m-d'),
                    $contrato->numero_contrato ?? '-',
                    $contrato->cliente->nombre ?? '-',
                    $p->tipo_pago,
                    $p->metodo_pago,
                    $p->cuentaDestino->nombre_cuenta ?? '-',
                    $p->monto,
                    $p->moneda_pago,
                    $tc,
                    $p->moneda_pago === 'BOB' ? $p->monto : $p->monto * $tc,
                    $p->codigo_seguimiento,
                    $p->observaciones,
                ];
            })
            ->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::aplicar($sheet);
    }
}

class GastosExtrasSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin
    ) {}

    public function title(): string
    {
        return 'Gastos Extras';
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Contrato',
            'Proveedor',
            'Cliente',
            'Categoría',
            'Concepto',
            'Estado',
            'Método Pago',
            'Monto',
            'Moneda',
            'TC',
            'Monto Bs',
        ];
    }

    public function array(): array
    {
        return GastoExtra::with(['contrato.proveedor', 'contrato.cliente'])->whereNull('deleted_at')->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin])->orderBy('fecha')->get()
            ->map(function ($g) {
                return [
                    optional($g->fecha)->format('Y-m-d'),
                    $g->contrato->numero_contrato ?? '-',
                    $g->contrato->proveedor->nombre ?? '-',
                    $g->contrato->cliente->nombre ?? '-',
                    $g->categoria,
                    $g->concepto,
                    $g->estado,
                    $g->metodo_pago,
                    $g->monto,
                    $g->moneda,
                    $g->tipo_cambio,
                    $g->monto_bolivianos,
                ];
            })->toArray();
    }

    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::aplicar($sheet);
    }
}

class TesoreriaCuentasSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    public function __construct(
        public string $fechaInicio,
        public string $fechaFin,
        public string $cuentaId
    ) {}

    public function title(): string
    {
        return 'Tesoreria';
    }

    public function headings(): array
    {
        return [
            'Cuenta',
            'Banco',
            'Moneda',
            'Saldo Inicial',
            'Ingresos',
            'Egresos',
            'Saldo Final Calculado',
            'Saldo Actual Sistema',
            'Diferencia',
            'Utilidad Cuenta',
        ];
    }
    public function array(): array
    {
        $cuentasQuery = CuentaEmpresa::with('banco')->whereNull('deleted_at');
        if ($this->cuentaId !== 'todas') {
            $cuentasQuery->where('id', $this->cuentaId);
        }
        return $cuentasQuery->get()
            ->map(function ($cuenta) {
                $movs = Movimiento::where('cuenta_empresa_id', $cuenta->id)->whereNull('deleted_at')->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin])->get();
                $ingresos = $movs->where('tipo', 'ingreso')->sum('monto_bolivianos');
                $egresos = $movs->where('tipo', 'egreso')->sum('monto_bolivianos');
                $ventas = $movs->where('tipo', 'ingreso')->whereIn('categoria', ['pago_cliente', 'anticipo_cliente'])->sum('monto_bolivianos');
                $proveedores = $movs->where('tipo', 'egreso')->where('categoria', 'pago_proveedor')->sum('monto_bolivianos');
                $camiones = $movs->where('tipo', 'egreso')->where('categoria', 'pago_camion')->sum('monto_bolivianos');
                $gastos = $movs->where('tipo', 'egreso')->where('categoria', 'gasto_extra')->sum('monto_bolivianos');

                $saldoFinalCalculado = $cuenta->saldo_inicial + $ingresos - $egresos;
                $utilidadCuenta = $ventas - $proveedores - $camiones - $gastos;
                return [
                    $cuenta->nombre_cuenta,
                    $cuenta->banco->nombre ?? '-',
                    $cuenta->moneda,
                    $cuenta->saldo_inicial,
                    $ingresos,
                    $egresos,
                    $saldoFinalCalculado,
                    $cuenta->saldo_actual,
                    $cuenta->saldo_actual - $saldoFinalCalculado,
                    $utilidadCuenta,
                ];
            })->toArray();
    }
    public function styles(Worksheet $sheet)
    {
        BaseSheetStyle::aplicar($sheet);
    }
}