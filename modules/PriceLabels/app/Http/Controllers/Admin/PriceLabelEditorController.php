<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\PriceLabels\Http\Requests\GeneratePriceLabelPdfRequest;
use Modules\PriceLabels\Http\Requests\PreviewPriceLabelExcelRequest;
use Modules\PriceLabels\Http\Requests\SavePriceLabelPositionsRequest;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelExcelImportService;
use Modules\PriceLabels\Services\PriceLabelGenerationService;
use Modules\PriceLabels\Services\PriceLabelPdfService;
use Modules\PriceLabels\Services\PriceLabelTemplateService;

class PriceLabelEditorController extends Controller
{
    public function __construct(
        private readonly PriceLabelTemplateService $templateService,
        private readonly PriceLabelExcelImportService $excelImportService,
        private readonly PriceLabelPdfService $pdfService,
        private readonly PriceLabelGenerationService $generationService
    ) {}

    public function savePositions(SavePriceLabelPositionsRequest $request, PriceLabelTemplate $priceLabelTemplate): JsonResponse
    {
        $data = $request->validated();

        $this->templateService->savePositions($priceLabelTemplate, $data['orientation'], $data['positions']);

        return response()->json(['success' => true]);
    }

    public function generate(GeneratePriceLabelPdfRequest $request, PriceLabelTemplate $priceLabelTemplate): Response
    {
        $data = $request->validated();
        $type = $data['type'];

        $hasImage = $type === 'horizontal' ? $priceLabelTemplate->image_horizontal : $priceLabelTemplate->image_vertical;

        if (! $hasImage) {
            throw ValidationException::withMessages([
                'type' => "La plantilla no tiene configurada la imagen base {$type}.",
            ]);
        }

        $rows = $this->excelImportService->read($request->file('excel_file'), $this->columnMap($priceLabelTemplate));

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'excel_file' => 'El archivo Excel esta vacio o no tiene el formato esperado.',
            ]);
        }

        $pdfContent = $this->pdfService->generate($priceLabelTemplate, $rows, $type)->output();

        $generation = $this->generationService->store($priceLabelTemplate, $type, count($rows), $pdfContent);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$generation->file_name.'"',
        ]);
    }

    public function previewExcel(PreviewPriceLabelExcelRequest $request, PriceLabelTemplate $priceLabelTemplate): JsonResponse
    {
        $rows = $this->excelImportService->read($request->file('excel_file'), $this->columnMap($priceLabelTemplate));

        return response()->json([
            'success' => true,
            'rows_count' => count($rows),
            'sample' => array_slice($rows, 0, 3),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function columnMap(PriceLabelTemplate $priceLabelTemplate): array
    {
        return collect($priceLabelTemplate->field_definitions ?: $this->templateService->defaultFieldDefinitions())
            ->pluck('excel_column', 'key')
            ->all();
    }
}
