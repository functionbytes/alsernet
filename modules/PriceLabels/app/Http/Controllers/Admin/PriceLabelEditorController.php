<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PriceLabels\Http\Requests\GeneratePriceLabelPdfRequest;
use Modules\PriceLabels\Http\Requests\PreviewPriceLabelExcelRequest;
use Modules\PriceLabels\Http\Requests\SavePriceLabelPositionsRequest;
use Modules\PriceLabels\Jobs\GeneratePriceLabelPdfJob;
use Modules\PriceLabels\Models\PriceLabelTemplate;
use Modules\PriceLabels\Services\PriceLabelExcelImportService;
use Modules\PriceLabels\Services\PriceLabelGenerationService;
use Modules\PriceLabels\Services\PriceLabelPdfService;
use Modules\PriceLabels\Services\PriceLabelTemplateService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function generate(GeneratePriceLabelPdfRequest $request, PriceLabelTemplate $priceLabelTemplate): JsonResponse
    {
        $data = $request->validated();
        $type = $data['type'];

        $hasImage = $type === 'horizontal' ? $priceLabelTemplate->image_horizontal : $priceLabelTemplate->image_vertical;

        if (! $hasImage) {
            throw ValidationException::withMessages([
                'type' => "La plantilla no tiene configurada la imagen base {$type}.",
            ]);
        }

        $sourceExcelPath = $this->generationService->storeUploadedExcel($request->file('excel_file'));
        $generation = $this->generationService->createPending($priceLabelTemplate, $type, $sourceExcelPath);

        GeneratePriceLabelPdfJob::dispatch($generation);

        return response()->json([
            'success' => true,
            'generation_id' => $generation->id,
            'status_url' => route('pricelabels.history.status', $generation),
        ]);
    }

    public function previewExcel(PreviewPriceLabelExcelRequest $request, PriceLabelTemplate $priceLabelTemplate): JsonResponse
    {
        $rows = $this->excelImportService->read($request->file('excel_file'), $this->templateService->columnMap($priceLabelTemplate));

        return response()->json([
            'success' => true,
            'rows_count' => count($rows),
            'sample' => array_slice($rows, 0, 3),
        ]);
    }

    public function downloadExcelTemplate(PriceLabelTemplate $priceLabelTemplate): StreamedResponse
    {
        $this->authorize('view', $priceLabelTemplate);

        $definitions = $priceLabelTemplate->field_definitions ?: $this->templateService->defaultFieldDefinitions();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($definitions as $definition) {
            $sheet->setCellValue($definition['excel_column'].'1', strtoupper($definition['label']));
        }

        $fileName = 'plantilla-excel-'.Str::slug($priceLabelTemplate->name).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
