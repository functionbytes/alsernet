<?php

namespace Modules\HelpdeskErp\Services\ErpInvoice;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Pinta la copia informativa de una factura de Gestión con DomPDF
 * (vista helpdeskerp::pdf.invoice, DejaVu Sans para acentos y €).
 *
 * Es una COPIA: la factura fiscal la emite Gestión. La vista lleva marca de
 * agua y pie "Copia informativa — no válida como factura".
 */
class ErpInvoicePdfRenderer
{
    public const WATERMARK = 'Copia informativa — no válida como factura';

    /**
     * @param  array<string, mixed>  $detail  data de invoiceDetail (shape del manager)
     * @return array{content: string, filename: string, document: array<string, mixed>}
     */
    public function render(array $detail, ?string $generatedBy = null): array
    {
        $doc = ErpInvoiceDocument::fromDetail($detail);

        $pdf = Pdf::loadView('helpdeskerp::pdf.invoice', [
            'doc' => $doc,
            'watermark' => (string) config('helpdeskErp.ext.invoice.watermark', self::WATERMARK),
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $generatedBy,
        ])->setPaper('a4')
            // Solo los glifos usados de DejaVu: ~40 KB en vez de ~900 KB.
            ->setOption('isFontSubsettingEnabled', true);

        return [
            'content' => $pdf->output(),
            'filename' => ErpInvoiceDocument::filename($detail),
            'document' => $doc,
        ];
    }

    /** Importe en formato español: "1.234,56 €". */
    public static function money(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return ($value < 0 ? '−' : '').number_format(abs($value), 2, ',', '.').' €';
    }

    /** "21 %", "5,2 %". */
    public static function percent(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $s = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');

        return $s.' %';
    }

    /** Unidades sin decimales vacíos: "1", "2,5". */
    public static function units(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    /** Precio unitario con 4 decimales como máximo, sin ceros de relleno. */
    public static function price(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        $s = number_format($value, 4, ',', '.');
        [$int, $dec] = explode(',', $s);
        $dec = rtrim($dec, '0');
        $dec = str_pad($dec, 2, '0');

        return $int.','.$dec.' €';
    }
}
