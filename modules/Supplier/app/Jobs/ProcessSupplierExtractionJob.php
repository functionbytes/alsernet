<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Services\Extraction\ContentFileStorage;
use Modules\Supplier\Services\Extraction\SourceExtractionDriverFactory;
use Modules\Supplier\Services\ExtractionService;

/**
 * Process Supplier Extraction Job
 *
 * Loads the appropriate extraction driver for a source, runs extraction,
 * persists results via ExtractionService and dispatches AI content jobs.
 *
 * @property int $tries Maximum number of retry attempts
 * @property int $timeout Job timeout in seconds
 * @property array $backoff Backoff delays between retries
 * @property bool $failOnTimeout Whether to fail the job on timeout
 */
class ProcessSupplierExtractionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public bool $failOnTimeout = true;

    public function __construct(
        protected int $sourceId,
        protected string $batchType = 'manual',
        protected ?string $batchId = null
    ) {
        $this->onQueue('supplier-extraction');
    }

    /**
     * Execute the job.
     */
    public function handle(
        ExtractionService $extractionService,
        SourceExtractionDriverFactory $driverFactory,
        ContentFileStorage $fileStorage
    ): void {
        $batch = null;

        try {
            $source = Source::with(['configurations'])->findOrFail($this->sourceId);

            Log::info('Starting supplier extraction job', [
                'source_id' => $source->id,
                'source_label' => $source->label,
                'batch_type' => $this->batchType,
                'attempt' => $this->attempts(),
            ]);

            $batch = $this->resolveBatch($extractionService, $source);

            if ($batch->isPending()) {
                $batch->markAsStarted();
            }

            $driver = $driverFactory->make($source);
            $products = $driver->extract($source, $batch);

            foreach ($products as $productData) {
                try {
                    $result = $extractionService->processExtractionResult($productData, $batch);
                    $filePath = $fileStorage->save($result);
                    $result->update(['source_file' => $filePath]);
                } catch (\Exception $e) {
                    Log::warning('ProcessSupplierExtractionJob: skipping product due to error', [
                        'source_id' => $source->id,
                        'error' => $e->getMessage(),
                        'product' => $productData['reference'] ?? $productData['name'] ?? 'unknown',
                    ]);
                }
            }

            $extractionService->completeExtraction($batch);

            $source->update([
                'last_processed_at' => now(),
                'last_batch_status' => 'completed',
            ]);

            $this->dispatchAiContentJobs($batch);

            Log::info('Supplier extraction job completed', [
                'source_id' => $source->id,
                'batch_id' => $batch->uid,
                'total_items' => $batch->total_items,
                'new_items' => $batch->new_items,
                'updated_items' => $batch->updated_items,
            ]);

        } catch (\Exception $e) {
            Log::error('Supplier extraction job failed', [
                'source_id' => $this->sourceId,
                'batch_id' => $this->batchId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($batch && $this->attempts() >= $this->tries) {
                $batch->markAsFailed();
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Supplier extraction job permanently failed', [
            'source_id' => $this->sourceId,
            'batch_id' => $this->batchId,
            'exception' => $exception->getMessage(),
        ]);

        if ($this->batchId) {
            ExtractionBatch::where('uid', $this->batchId)->first()?->markAsFailed();
        }

        Source::find($this->sourceId)?->update(['last_batch_status' => 'failed']);
    }

    /**
     * Resolve existing batch or create a new one.
     */
    private function resolveBatch(ExtractionService $extractionService, Source $source): ExtractionBatch
    {
        if ($this->batchId) {
            $batch = ExtractionBatch::where('uid', $this->batchId)->first();

            if ($batch) {
                return $batch;
            }
        }

        $batch = $extractionService->startExtraction($source, $this->batchType);
        $this->batchId = $batch->uid;

        return $batch;
    }

    /**
     * Dispatch AI content generation for new and updated results.
     */
    private function dispatchAiContentJobs(ExtractionBatch $batch): void
    {
        if (! class_exists(GenerateAiContentJob::class)) {
            Log::warning('ProcessSupplierExtractionJob: GenerateAiContentJob not found, skipping AI generation', [
                'batch_id' => $batch->uid,
            ]);

            return;
        }

        // Stream the results instead of loading every ExtractionResult (which
        // carries large JSON payloads) into memory at once.
        $dispatched = 0;

        $batch->results()
            ->whereIn('status', ['new', 'updated'])
            ->select(['id', 'uid', 'batch_id'])
            ->cursor()
            ->each(function ($result) use (&$dispatched): void {
                GenerateAiContentJob::dispatch($result)->onQueue('ai-content-generation');
                $dispatched++;
            });

        if ($dispatched > 0) {
            Log::info('ProcessSupplierExtractionJob: dispatched AI content jobs', [
                'batch_id' => $batch->uid,
                'count' => $dispatched,
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return array_filter([
            'supplier-extraction',
            "source:{$this->sourceId}",
            $this->batchId ? "batch:{$this->batchId}" : null,
        ]);
    }
}
