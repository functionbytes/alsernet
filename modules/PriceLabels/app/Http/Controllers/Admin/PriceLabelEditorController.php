<?php

namespace Modules\PriceLabels\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PriceLabels\Http\Requests\GeneratePriceLabelPdfRequest;
use Modules\PriceLabels\Http\Requests\PreviewPriceLabelExcelRequest;
use Modules\PriceLabels\Http\Requests\PreviewPriceLabelPdfRequest;
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

        $this->templateService->savePositions(
            $priceLabelTemplate,
            $data['orientation'],
            $data['positions'],
            $data['fields'] ?? null
        );

        return response()->json(['success' => true]);
    }

    public function generate(GeneratePriceLabelPdfRequest $request, PriceLabelTemplate $priceLabelTemplate): JsonResponse
    {
        $types = array_values(array_unique($request->validated()['types']));

        foreach ($types as $type) {
            $hasImage = $type === 'horizontal' ? $priceLabelTemplate->image_horizontal : $priceLabelTemplate->image_vertical;

            if (! $hasImage) {
                throw ValidationException::withMessages([
                    'types' => "La plantilla no tiene configurada la imagen base {$type}.",
                ]);
            }
        }

        $sourceExcelPath = $this->generationService->storeUploadedExcel($request->file('excel_file'));

        $generations = array_map(function (string $type) use ($priceLabelTemplate, $sourceExcelPath): array {
            $generation = $this->generationService->createPending($priceLabelTemplate, $type, $sourceExcelPath);

            GeneratePriceLabelPdfJob::dispatch($generation);

            return [
                'type' => $type,
                'generation_id' => $generation->id,
                'status_url' => route('pricelabels.history.status', $generation),
            ];
        }, $types);

        return response()->json([
            'success' => true,
            'generations' => $generations,
        ]);
    }

    public function previewPdf(PreviewPriceLabelPdfRequest $request, PriceLabelTemplate $priceLabelTemplate): Response
    {
        $data = $request->validated();
        $orientation = $data['orientation'];

        $hasImage = $orientation === 'horizontal' ? $priceLabelTemplate->image_horizontal : $priceLabelTemplate->image_vertical;

        if (! $hasImage) {
            throw ValidationException::withMessages([
                'orientation' => "La plantilla no tiene configurada la imagen base {$orientation}.",
            ]);
        }

        $preview = $this->templateService->withOverrides(
            $priceLabelTemplate,
            $orientation,
            $data['positions'] ?? null,
            $data['fields'] ?? null
        );

        $row = $this->templateService->sampleRowFor(
            $priceLabelTemplate,
            $this->generationService->lastSampleRow($priceLabelTemplate)
        );

        $rows = array_fill(0, $this->templateService->slotsPerPage($priceLabelTemplate, $orientation), $row);

        $pdf = $this->pdfService->generate($preview, $rows, $orientation);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="previsualizacion.pdf"',
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
