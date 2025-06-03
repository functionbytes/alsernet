<?php

namespace App\Services\Return;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfGeneratorService
{
    /**
     * Generar PDF desde una vista
     */
    public function generateFromView(string $view, array $data = [], array $options = []): \Barryvdh\DomPDF\PDF
    {
        $defaultOptions = [
            'format' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
        ];

        $options = array_merge($defaultOptions, $options);

        $pdf = Pdf::loadView($view, $data);

        $pdf->setPaper($options['format'], $options['orientation']);
        $pdf->setOptions([
            'margin_top' => $options['margin_top'],
            'margin_right' => $options['margin_right'],
            'margin_bottom' => $options['margin_bottom'],
            'margin_left' => $options['margin_left'],
        ]);

        return $pdf;
    }

    /**
     * Generar PDF desde HTML
     */
    public function generateFromHtml(string $html, array $options = []): \Barryvdh\DomPDF\PDF
    {
        $defaultOptions = [
            'format' => 'A4',
            'orientation' => 'portrait',
        ];

        $options = array_merge($defaultOptions, $options);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper($options['format'], $options['orientation']);

        return $pdf;
    }

    /**
     * Guardar PDF en storage
     */
    public function savePdf(\Barryvdh\DomPDF\PDF $pdf, string $path): bool
    {
        return Storage::put($path, $pdf->output());
    }

    /**
     * Generar y descargar PDF
     */
    public function downloadPdf(\Barryvdh\DomPDF\PDF $pdf, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->download($filename);
    }

    /**
     * Mostrar PDF en el navegador
     */
    public function streamPdf(\Barryvdh\DomPDF\PDF $pdf, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return $pdf->stream($filename);
    }
}
