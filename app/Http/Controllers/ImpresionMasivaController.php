<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\PagoCamion;
use App\Models\PagoCliente;
use App\Models\PagoProveedor;
use App\Models\Tramo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class ImpresionMasivaController extends Controller
{
    use \App\Http\Controllers\Concerns\SirveArchivoAdjunto;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('impresion_masiva.index');
    }

    public function buscar(Request $request)
    {
        $data = $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'tipos' => 'required|array|min:1',
            'tipos.*' => 'in:contratos,documentos_entrega,vouchers_proveedor,vouchers_cliente,vouchers_camion',
        ]);

        $documentos = $this->recolectarDocumentos($data['desde'], $data['hasta'], $data['tipos']);

        if (empty($documentos)) {
            return back()->withErrors(['tipos' => 'No se encontraron documentos adjuntos en ese rango de fechas.']);
        }

        return $this->generarPdfUnico($documentos, $data['desde'], $data['hasta']);
    }

    /**
     * Junta los documentos adjuntos de cada tipo seleccionado dentro del rango
     * de fechas, usando la fecha de negocio de cada registro (no la fecha de
     * subida del archivo).
     */
    private function recolectarDocumentos(string $desde, string $hasta, array $tipos): array
    {
        $documentos = [];

        if (in_array('contratos', $tipos)) {
            Contrato::whereNotNull('documento_pdf')
                ->whereBetween('fecha_inicio', [$desde, $hasta])
                ->get()
                ->each(function ($c) use (&$documentos) {
                    $documentos[] = [
                        'tipo'       => 'Contrato',
                        'referencia' => $c->numero_contrato,
                        'fecha'      => $c->fecha_inicio?->format('d/m/Y'),
                        'archivo'    => $c->documento_pdf,
                    ];
                });
        }

        if (in_array('documentos_entrega', $tipos)) {
            Tramo::whereNotNull('documento_entrega')
                ->whereBetween('fecha_llegada', [$desde, $hasta])
                ->with('contratoCamion.contrato')
                ->get()
                ->each(function ($t) use (&$documentos) {
                    $documentos[] = [
                        'tipo'       => 'Documento de entrega',
                        'referencia' => $t->contratoCamion?->contrato?->numero_contrato ?? '—',
                        'fecha'      => $t->fecha_llegada?->format('d/m/Y'),
                        'archivo'    => $t->documento_entrega,
                    ];
                });
        }

        if (in_array('vouchers_proveedor', $tipos)) {
            PagoProveedor::whereNotNull('voucher')
                ->whereBetween('fecha_pago', [$desde, $hasta])
                ->with('contrato')
                ->get()
                ->each(function ($p) use (&$documentos) {
                    $documentos[] = [
                        'tipo'       => 'Voucher pago a proveedor',
                        'referencia' => $p->contrato?->numero_contrato ?? '—',
                        'fecha'      => $p->fecha_pago?->format('d/m/Y'),
                        'archivo'    => $p->voucher,
                    ];
                });
        }

        if (in_array('vouchers_cliente', $tipos)) {
            PagoCliente::whereNotNull('voucher')
                ->whereBetween('fecha_pago', [$desde, $hasta])
                ->with('tramo.contratoCamion.contrato')
                ->get()
                ->each(function ($p) use (&$documentos) {
                    $documentos[] = [
                        'tipo'       => 'Voucher cobro a cliente',
                        'referencia' => $p->tramo?->contratoCamion?->contrato?->numero_contrato ?? '—',
                        'fecha'      => $p->fecha_pago?->format('d/m/Y'),
                        'archivo'    => $p->voucher,
                    ];
                });
        }

        if (in_array('vouchers_camion', $tipos)) {
            PagoCamion::whereNotNull('voucher')
                ->whereBetween('fecha_pago', [$desde, $hasta])
                ->with('contratoCamion.contrato')
                ->get()
                ->each(function ($p) use (&$documentos) {
                    $documentos[] = [
                        'tipo'       => 'Voucher pago de flete',
                        'referencia' => $p->contratoCamion?->contrato?->numero_contrato ?? '—',
                        'fecha'      => $p->fecha_pago?->format('d/m/Y'),
                        'archivo'    => $p->voucher,
                    ];
                });
        }

        return $documentos;
    }

    /**
     * Fusiona todo en un solo PDF: portada+índice, luego cada documento
     * (imágenes convertidas a PDF de una página vía dompdf, PDFs existentes
     * importados página por página) con FPDI, listo para enviar a imprimir
     * de una sola vez.
     */
    private function generarPdfUnico(array $documentos, string $desde, string $hasta)
    {
        $indicePdf = Pdf::loadView('pdf.impresion-masiva-indice', [
            'documentos' => $documentos,
            'desde'      => $desde,
            'hasta'      => $hasta,
        ])->setPaper('a4')->output();

        $tmpIndice = tempnam(sys_get_temp_dir(), 'idx_') . '.pdf';
        file_put_contents($tmpIndice, $indicePdf);

        $fpdi = new Fpdi();
        $this->agregarPaginasDesdeArchivo($fpdi, $tmpIndice);
        unlink($tmpIndice);

        $tmpImagen = null;
        foreach ($documentos as $doc) {
            $path = Storage::disk('public')->path($doc['archivo']);
            if (!file_exists($path)) continue;

            $mime = mime_content_type($path);

            if (str_starts_with($mime, 'image/')) {
                $tmpImagen = tempnam(sys_get_temp_dir(), 'img_') . '.pdf';
                file_put_contents($tmpImagen, $this->imagenComoPdf($path, $mime)->output());
                $this->agregarPaginasDesdeArchivo($fpdi, $tmpImagen, $doc['referencia']);
                unlink($tmpImagen);
            } else {
                // Un PDF corrupto o protegido de un documento viejo no debe
                // tumbar la fusión completa: se omite y se sigue con el resto.
                try {
                    $this->agregarPaginasDesdeArchivo($fpdi, $path, $doc['referencia']);
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        $nombre = 'impresion_masiva_' . $desde . '_a_' . $hasta . '.pdf';

        return response($fpdi->Output('S'), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombre . '"',
        ]);
    }

    private function agregarPaginasDesdeArchivo(Fpdi $fpdi, string $path, ?string $codigoContrato = null): void
    {
        $totalPaginas = $fpdi->setSourceFile($path);
        for ($p = 1; $p <= $totalPaginas; $p++) {
            $plantilla = $fpdi->importPage($p);
            $tamano = $fpdi->getTemplateSize($plantilla);
            $fpdi->AddPage($tamano['orientation'], [$tamano['width'], $tamano['height']]);
            $fpdi->useTemplate($plantilla);

            if ($codigoContrato) {
                $fpdi->SetFont('Helvetica', '', 8);
                $fpdi->SetTextColor(120, 120, 120);
                $ancho = $fpdi->GetStringWidth($codigoContrato) + 4;
                $fpdi->SetXY($tamano['width'] - $ancho - 5, 5);
                $fpdi->Cell($ancho, 5, $codigoContrato);
            }
        }
    }
}
