<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
class ArrayExport implements FromArray, WithTitle, WithEvents, ShouldAutoSize
{
    public function __construct(private string $titulo,private array $filtros,private array $columnas,private array $filas,private array $totales = [],private array $tiposColumna = []) 
        {}
    public function array(): array
    {
        return [];
    }
    public function title(): string
    {
        $limpio = preg_replace('/[\[\]\*\/\\\\\?:]/', '', $this->titulo);
        return mb_substr($limpio, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalCols = max(count($this->columnas), 2);
                $ultimaColumna = Coordinate::stringFromColumnIndex($totalCols);
                $azulEncabezado = '2E5395';
                $grisTotales    = 'E7ECF3';
                $grisAlterno    = 'F4F7FC';
                $grisBorde      = 'D0D5DD';
                $fila = 1;
                $sheet->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
                $sheet->setCellValue("A{$fila}", $this->titulo);
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(15);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension($fila)->setRowHeight(26);
                $fila += 2;
                if (!empty($this->filtros)) {
                    $sheet->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
                    $sheet->setCellValue("A{$fila}", 'Filtros aplicados');
                    $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setItalic(true)->setSize(11);
                    $fila++;
                    foreach ($this->filtros as $label => $valor) {
                        $sheet->setCellValue("A{$fila}", $label . ':');
                        $sheet->getStyle("A{$fila}")->getFont()->setBold(true);
                        $sheet->setCellValue("B{$fila}", $valor !== '' && $valor !== null ? $valor : 'Todos');
                        $fila++;
                    }
                    $fila++;
                }

                $filaEncabezado = $fila;
                foreach ($this->columnas as $i => $col) {
                    $col_letra = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col_letra}{$filaEncabezado}", $col);
                }
                $rangoEncabezado = "A{$filaEncabezado}:{$ultimaColumna}{$filaEncabezado}";
                $sheet->getStyle($rangoEncabezado)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($rangoEncabezado)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($azulEncabezado);
                $sheet->getStyle($rangoEncabezado)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($filaEncabezado)->setRowHeight(20);
                $fila++;

                $primeraFilaDatos = $fila;
                foreach ($this->filas as $registro) {
                    foreach ($registro as $i => $valor) {
                        $col_letra = Coordinate::stringFromColumnIndex($i + 1);
                        $sheet->setCellValue("{$col_letra}{$fila}", $valor);
                    }
                    $fila++;
                }
                $ultimaFilaDatos = $fila - 1;

                if ($ultimaFilaDatos >= $primeraFilaDatos) {
                    $rangoDatos = "A{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFilaDatos}";
                    $sheet->getStyle($rangoDatos)->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($grisBorde);
                   for ($r = $primeraFilaDatos; $r <= $ultimaFilaDatos; $r++) {
                        if (($r - $primeraFilaDatos) % 2 === 1) {
                            $sheet->getStyle("A{$r}:{$ultimaColumna}{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grisAlterno);
                        }
                    }

                    foreach ($this->columnas as $i => $col) {
                        $tipo = $this->tiposColumna[$i] ?? 'texto';
                        $col_letra = Coordinate::stringFromColumnIndex($i + 1);
                        $rango = "{$col_letra}{$primeraFilaDatos}:{$col_letra}{$ultimaFilaDatos}";

                        if ($tipo === 'numero') {
                            $sheet->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $sheet->getStyle($rango)->getNumberFormat()->setFormatCode('#,##0.00');
                        } elseif ($tipo === 'centro') {
                            $sheet->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        } else {
                            $sheet->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        }
                    }
                } else {
                    $sheet->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
                    $sheet->setCellValue("A{$fila}", 'No hay resultados para los filtros seleccionados.');
                    $sheet->getStyle("A{$fila}")->getFont()->setItalic(true);
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $fila++;
                }

                $fila++;
                if (!empty($this->totales)) {
                    $sheet->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");
                    $sheet->setCellValue("A{$fila}", 'Totales');
                    $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(12);
                    $sheet->getStyle("A{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($grisTotales);
                    $fila++;
                    foreach ($this->totales as $label => $valor) {
                        $sheet->setCellValue("A{$fila}", $label . ':');
                        $sheet->getStyle("A{$fila}")->getFont()->setBold(true);
                        $sheet->setCellValue("B{$fila}", (float) $valor);
                        $sheet->getStyle("B{$fila}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("B{$fila}")->getFont()->setBold(true);
                        $fila++;
                    }
                    $fila++;
                }
                $sheet->setCellValue("A{$fila}", 'Generado el ' . now()->format('d/m/Y H:i'));
                $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('808080');
                foreach ($this->columnas as $i => $col) {
                    $col_letra = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->getColumnDimension($col_letra)->setWidth(max(14, mb_strlen($col) + 4));
                }
            },
        ];
    }
}
