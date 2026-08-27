<?php

namespace Modules\PriceLabels\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelPdfService
{
    private const MM_PER_POINT = 2.83464567;

    private const CANVAS = [
        'vertical' => ['w' => 700, 'h' => 990, 'page_w_mm' => 210, 'page_h_mm' => 297],
        'horizontal' => ['w' => 1133, 'h' => 720, 'page_w_mm' => 340, 'page_h_mm' => 216],
    ];

    public function __construct(
        private readonly PriceLabelTemplateService $templateService,
        private readonly PriceLabelFontService $fontService,
        private readonly PriceLabelBarcodeService $barcodeService
    ) {}

    public function generate(PriceLabelTemplate $template, array $rows, string $orientation): PdfDocument
    {
        $canvas = self::CANVAS[$orientation];
        $pages = $this->buildPages($template, $rows, $orientation, $canvas);

        $backgroundPath = $this->imagePath(
            $orientation === 'horizontal' ? $template->image_horizontal : $template->image_vertical
        );

        $pdf = Pdf::loadView('pricelabels::pdf.label-sheet', [
            'pages' => $pages,
            'backgroundPath' => $backgroundPath,
            'pageWidthMm' => $canvas['page_w_mm'],
            'pageHeightMm' => $canvas['page_h_mm'],
            'fontFaceCss' => $this->fontService->fontFaceCss(forPdf: true),
        ]);

        if ($orientation === 'horizontal') {
            $widthPt = $canvas['page_w_mm'] * self::MM_PER_POINT;
            $heightPt = $canvas['page_h_mm'] * self::MM_PER_POINT;

            // No pasar 'landscape' aqui: DomPDF invierte ancho/alto del array
            // custom cuando la orientacion es 'landscape', aunque el array ya
            // sea apaisado (ancho > alto), dejando la pagina en vertical.
            return $pdf->setPaper([0, 0, $widthPt, $heightPt]);
        }

        return $pdf->setPaper('a4', 'portrait');
    }

    private function buildPages(PriceLabelTemplate $template, array $rows, string $orientation, array $canvas): array
    {
        $fields = $this->templateService->fieldsWithDefaults($template);
        $positions = $this->templateService->positionsWithDefaults($template, $orientation);
        $keys = $this->templateService->fieldKeys($template->field_definitions);
        $definitions = collect($template->field_definitions ?: $this->templateService->defaultFieldDefinitions());
        $typeByKey = $definitions->pluck('type', 'key')->all();
        $symbologyByKey = $definitions->pluck('barcode_type', 'key')->all();

        $slotsPerPage = $orientation === 'horizontal'
            ? max((int) $template->horizontal_rows * (int) $template->horizontal_columns, 1)
            : max((int) $template->vertical_rows * (int) $template->vertical_columns, 1);

        $pages = [];
        foreach (array_chunk($rows, $slotsPerPage) as $chunk) {
            $slots = [];
            foreach (array_values($chunk) as $index => $row) {
                $slots[] = $this->buildSlot($template, $row, $index + 1, $fields, $positions, $canvas, $orientation, $keys, $typeByKey, $symbologyByKey);
            }
            $pages[] = $slots;
        }

        return $pages;
    }

    private function buildSlot(PriceLabelTemplate $template, array $row, int $slot, array $fields, array $positions, array $canvas, string $orientation, array $keys, array $typeByKey, array $symbologyByKey): array
    {
        $texts = ['label' => $template->label_text ?: 'Precio recomendado:'];
        foreach ($keys as $key) {
            if ($key === 'label') {
                continue;
            }

            $value = $row[$key] ?? '';
            $texts[$key] = ($typeByKey[$key] ?? 'text') === 'price' ? $this->formatEuro($value) : $value;
        }

        $isHorizontal = $orientation === 'horizontal';
        $elements = [];
        $stacks = $this->fontService->cssStacks();

        foreach ($keys as $key) {
            $style = $fields[$key] ?? [];
            $pos = $positions[$key][$slot] ?? ['x' => 50, 'y' => 50];
            $family = $isHorizontal ? ($style['font_family_h'] ?? 'helvetica') : ($style['font_family'] ?? 'helvetica');
            $type = $typeByKey[$key] ?? 'text';

            $imageSrc = $this->barcodeService->isImageType($type)
                ? $this->barcodeService->pngDataUri($type, (string) ($texts[$key] ?? ''), $symbologyByKey[$key] ?? 'C128')
                : null;

            $elements[$key] = [
                // Si el codigo no se pudo generar (valor invalido para esa
                // simbologia) se cae al texto plano en vez de dejar un hueco.
                'text' => $texts[$key] ?? '',
                'image_src' => $imageSrc,
                'left' => round($pos['x'] * $canvas['page_w_mm'] / $canvas['w'], 2),
                'top' => round($pos['y'] * $canvas['page_h_mm'] / $canvas['h'], 2),
                'width' => round(($style['box_w'] ?? 150) * $canvas['page_w_mm'] / $canvas['w'], 2),
                'height' => round(($style['box_h'] ?? 30) * $canvas['page_h_mm'] / $canvas['h'], 2),
                'color' => $style['color'] ?? '#000000',
                'font_family' => $stacks[$family] ?? PriceLabelFontService::BUILTIN_STACKS['helvetica'],
                'font_size' => $isHorizontal ? ($style['font_size_h'] ?? 12) : ($style['font_size'] ?? 12),
                'bold' => (bool) ($style['bold'] ?? false),
                'italic' => (bool) ($style['italic'] ?? false),
            ];
        }

        return $elements;
    }

    private function imagePath(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        return Storage::disk('public')->path($relativePath);
    }

    private function formatEuro(string $raw): string
    {
        $value = trim(str_replace(['€', 'EUR', 'eur', 'Eur'], '', $raw));

        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $value)) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $value)) {
            $value = str_replace(',', '', $value);
        }

        $number = is_numeric($value) ? (float) $value : 0.0;
        $decimals = fmod($number, 1.0) !== 0.0 ? 2 : 0;

        return number_format($number, $decimals, ',', '.').'€';
    }
}
