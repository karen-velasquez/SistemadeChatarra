<?php

namespace App\Exports;

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\GastoExtra;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Proveedor;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteGeneralExport implements FromView, ShouldAutoSize, WithStyles, WithColumnWidths, WithEvents
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $proveedorId;
    protected $clienteId;
    protected $tipoContrato;

    public function __construct($fechaInicio, $fechaFin, $proveedorId = null, $clienteId = null, $tipoContrato = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->proveedorId = $proveedorId;
        $this->clienteId = $clienteId;
        $this->tipoContrato = $tipoContrato;
    }

    public function view(): View
    {
        $fechaInicio = $this->fechaInicio;
        $fechaFin = $this->fechaFin;

        $proveedorFiltro = $this->proveedorId
            ? Proveedor::find($this->proveedorId)?->nombre
            : 'TODOS';

        $clienteFiltro = $this->clienteId
            ? Cliente::find($this->clienteId)?->nombre
            : 'TODOS';

        $tipoContratoFiltro = $this->tipoContrato ?: 'TODOS';

        $contratosQuery = Contrato::with(['proveedor', 'cliente'])
            ->whereNull('deleted_at');

        if ($this->proveedorId) {
            $contratosQuery->where('proveedor_id', $this->proveedorId);
        }

        if ($this->clienteId) {
            $contratosQuery->where('cliente_id', $this->clienteId);
        }

        if ($this->tipoContrato) {
            $contratosQuery->where('tipo_contrato', $this->tipoContrato);
        }

        $contratosIds = $contratosQuery->pluck('id');

        $pagosProveedor = PagoProveedor::with('contrato.proveedor')
            ->whereNull('deleted_at')
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);

        if ($contratosIds->isNotEmpty()) {
            $pagosProveedor->whereIn('contrato_id', $contratosIds);
        }

        $pagosProveedor = $pagosProveedor->get();

        $gastosExtras = GastoExtra::with('contrato.proveedor')
            ->whereNull('deleted_at')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($contratosIds->isNotEmpty()) {
            $gastosExtras->whereIn('contrato_id', $contratosIds);
        }

        $gastosExtras = $gastosExtras->get();

        $cobrosCliente = PagoCliente::with(['tramo.contratoCamion.contrato.cliente'])
            ->whereNull('deleted_at')
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->get();

        $pagosCamiones = PagoCamion::with(['contratoCamion.contrato.proveedor', 'contratoCamion.camion'])
            ->whereNull('deleted_at')
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->get();

        return view('reportes.excel.general', compact(
            'fechaInicio',
            'fechaFin',
            'proveedorFiltro',
            'clienteFiltro',
            'tipoContratoFiltro',
            'pagosProveedor',
            'cobrosCliente',
            'gastosExtras',
            'pagosCamiones'
        ));
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 20,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
            2 => [
                'font' => [
                    'italic' => true,
                    'size' => 11,
                    'color' => ['rgb' => '64748B'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 28,
            'C' => 38,
            'D' => 18,
            'E' => 20,
            'F' => 18,
            'G' => 24,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A8');
                $sheet->getDefaultRowDimension()->setRowHeight(24);
                $sheet->getRowDimension(1)->setRowHeight(34);
                $sheet->getRowDimension(2)->setRowHeight(24);

                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');

                $sheet->getStyle('A:G')->getFont()->setName('Segoe UI');
                $sheet->getStyle('A:G')->getAlignment()->setVertical('center')->setWrapText(true);

                $sheet->getStyle('A1:G500')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A1:G500')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('A1:G2')->getAlignment()->setHorizontal('center');

                $sheet->getStyle('A1:G1')
                    ->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setRGB('0F172A');
            },
        ];
    }
}