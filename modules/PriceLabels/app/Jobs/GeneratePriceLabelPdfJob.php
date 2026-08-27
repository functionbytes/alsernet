<?php

namespace Modules\PriceLabels\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\PriceLabels\Models\PriceLabelGeneration;
use Modules\PriceLabels\Notifications\PriceLabelGenerationCompletedNotification;
use Modules\PriceLabels\Notifications\PriceLabelGenerationFailedNotification;
use Modules\PriceLabels\Services\PriceLabelExcelImportService;
use Modules\PriceLabels\Services\PriceLabelGenerationService;
use Modules\PriceLabels\Services\PriceLabelPdfService;
use Modules\PriceLabels\Services\PriceLabelTemplateService;

class GeneratePriceLabelPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 15;

    public function __construct(
        private readonly PriceLabelGeneration $generation
    ) {
        $this->onQueue('exports');
    }

    public function handle(
        PriceLabelExcelImportService $excelImportService,
        PriceLabelPdfService $pdfService,
        PriceLabelTemplateService $templateService,
        PriceLabelGenerationService $generationService
    ): void {
        $template = $this->generation->template;

        if (! $template) {
            $generationService->markFailed($this->generation, 'La plantilla ya no existe.');

            return;
        }

        $rows = $excelImportService->read(
            $this->generation->source_excel_path,
            $templateService->columnMap($template),
            'public'
        );

        if (empty($rows)) {
            $generationService->markFailed($this->generation, 'El archivo Excel esta vacio o no tiene el formato esperado.');

            return;
        }

        $pdfContent = $pdfService->generate($template, $rows, $this->generation->type)->output();

        $generationService->markCompleted($this->generation, count($rows), $rows[0] ?? null, $pdfContent);

        $this->generation->generatedBy?->notify(new PriceLabelGenerationCompletedNotification($this->generation->fresh()));
    }

    public function failed(\Throwable $exception): void
    {
        app(PriceLabelGenerationService::class)->markFailed($this->generation, $exception->getMessage());

        $this->generation->generatedBy?->notify(new PriceLabelGenerationFailedNotification($this->generation));

        Log::error('PriceLabels PDF generation job failed', [
            'generation_id' => $this->generation->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
