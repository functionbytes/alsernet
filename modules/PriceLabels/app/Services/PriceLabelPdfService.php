<?php

namespace Modules\PriceLabels\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Facades\Storage;
use Modules\PriceLabels\Models\PriceLabelTemplate;

class PriceLabelPdfService
{
    private const MM_PER_POINT = 2.83464567;

    /**
     * Diferencia (en em) entre donde DomPDF y el navegador colocan la primera
     * linea base de un bloque con el mismo `line-height`. Sale de las metricas
     * verticales de la fuente, que cada motor lee de tablas distintas, por eso
     * hay que medirla y no se puede deducir del CSS.
     *
     * Medido con la fuente actual (Noto Sans SC) comparando el PDF generado
     * contra el lienzo del editor. Si se cambia la tipografia base y los
     * campos vuelven a descuadrarse en vertical, hay que recalibrarla.
     */
    private const BASELINE_EM_OFFSET = 0.397;

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
            $fontSize = (float) ($isHorizontal ? ($style['font_size_h'] ?? 12) : ($style['font_size'] ?? 12));

            // La caja tambien es por orientacion; si la plantilla es anterior
            // a esa separacion, el horizontal cae en el tamano vertical.
            $boxW = $isHorizontal ? ($style['box_w_h'] ?? $style['box_w'] ?? 150) : ($style['box_w'] ?? 150);
            $boxH = $isHorizontal ? ($style['box_h_h'] ?? $style['box_h'] ?? 30) : ($style['box_h'] ?? 30);

            $imageSrc = $this->barcodeService->isImageType($type)
                ? $this->barcodeService->pngDataUri($type, (string) ($texts[$key] ?? ''), $symbologyByKey[$key] ?? 'C128')
                : null;

            // Con el mismo line-height, DomPDF deja la primera linea base mas
            // abajo que el navegador (lee otras metricas verticales de la
            // fuente). El desfase es proporcional al tamano de letra, asi que
            // dos campos con tamanos distintos que en el editor se veian
            // alineados salian descuadrados en el PDF. Se sube el bloque para
            // que la linea base caiga donde la pinta el editor.
            $baselineShiftMm = $imageSrc ? 0.0 : self::BASELINE_EM_OFFSET * $fontSize * 25.4 / 72;

            $elements[$key] = [
                // Si el codigo no se pudo generar (valor invalido para esa
                // simbologia) se cae al texto plano en vez de dejar un hueco.
                'text' => $texts[$key] ?? '',
                'image_src' => $imageSrc,
                'left' => round($pos['x'] * $canvas['page_w_mm'] / $canvas['w'], 2),
                'top' => round($pos['y'] * $canvas['page_h_mm'] / $canvas['h'] - $baselineShiftMm, 2),
                'width' => round($boxW * $canvas['page_w_mm'] / $canvas['w'], 2),
                'height' => round($boxH * $canvas['page_h_mm'] / $canvas['h'], 2),
                'color' => $style['color'] ?? '#000000',
                'font_family' => $stacks[$family] ?? PriceLabelFontService::BUILTIN_STACKS['helvetica'],
                'font_size' => $fontSize,
                'bold' => (bool) ($style['bold'] ?? false),
                'italic' => (bool) ($style['italic'] ?? false),
                'align' => in_array($style['align'] ?? null, ['left', 'center', 'right'], true)
                    ? $style['align']
                    : 'center',
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
