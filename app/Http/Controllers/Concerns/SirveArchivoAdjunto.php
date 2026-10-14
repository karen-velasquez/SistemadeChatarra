<?php

namespace App\Http\Controllers\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;

trait SirveArchivoAdjunto
{
    /**
     * Sirve un archivo adjunto (PDF o imagen) para verlo inline o descargarlo.
     * Con ?print=1 e imagen (JPG/PNG), la envuelve en un PDF de una página para
     * que se abra en el visor nativo del navegador con su vista previa de
     * impresión — un PDF ya se puede imprimir así de por sí, no hace falta nada.
     */
    protected function responderArchivo(string $path, string $nombreDescarga, bool $download = false, bool $print = false)
    {
        $mime = mime_content_type($path);
        $esImagen = str_starts_with($mime, 'image/');

        if ($print && $esImagen) {
            $imagenBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            $pdf = Pdf::loadView('pdf.imagen-para-imprimir', compact('imagenBase64'))->setPaper('a4');
            return $pdf->stream(pathinfo($nombreDescarga, PATHINFO_FILENAME) . '.pdf');
        }

        if ($download) {
            return response()->download($path, $nombreDescarga);
        }

        return response()->file($path, ['Content-Type' => $mime]);
    }
}
