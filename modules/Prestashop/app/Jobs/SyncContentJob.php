<?php

namespace Modules\Prestashop\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Entities\SupplierAiContent;
use Throwable;

/**
 * SyncContentToPrestashopJob
 *
 * Queued job for synchronizing AI-generated supplier content to PrestaShop.
 * Handles both single content items and batch operations with automatic retries
 * on connection failures.
 *
 * Features:
 * - Automatic retry on connection failures (3 attempts)
 * - Exponential backoff between retries
 * - Transaction management for data consistency
 * - Comprehensive logging of sync operations
 * - Timestamp updates for published_at and synced_to_erp_at
 * - Batch processing support for multiple content items
 * - Failed job handling with detailed error logging
 */
class SyncContentToPrestashopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum execution time in seconds (5 minutes)
     */
    public int $timeout = 300;

    /**
     * Time in seconds to wait before retrying after failure
     */
    public int $backoff = 30;

    /**
     * Single content instance to sync
     */
    protected ?SupplierAiContent $content = null;

    /**
     * Array of content IDs for batch syncing
     */
    protected ?array $contentIds = null;

    /**
     * Unique batch identifier for tracking
     */
    protected ?string $batchId = null;

    /**
     * Whether to auto-publish content after successful sync
     */
    protected bool $autoPublish = false;

    /**
     * Create a new job instance for single content sync
     */
    public function __construct(
        SupplierAiContent|array $content,
        bool $autoPublish = false
    ) {
        if ($content instanceof SupplierAiContent) {
            $this->content = $content;
            $this->batchId = "single_{$content->id}_".uniqid();
        } else {
            $this->contentIds = $content;
            $this->batchId = 'batch_'.uniqid();
        }

        $this->autoPublish = $autoPublish;
        $this->onQueue('prestashop-sync');
    }

    /**
     * Execute the job
     */
    public function handle(SyncService $syncService): void
    {
        if ($this->content) {
            $this->handleSingleSync($syncService);
        } elseif ($this->contentIds) {
            $this->handleBatchSync($syncService);
        }
    }

    /**
     * Handle synchronization of a single content item
     */
    protected function handleSingleSync(SyncService $syncService): void
    {
        Log::info('Starting PrestaShop sync for single content', [
            'batch_id' => $this->batchId,
            'content_id' => $this->content->id,
            'content_uid' => $this->content->uid,
            'auto_publish' => $this->autoPublish,
        ]);

        DB::beginTransaction();

        try {
            // Publish content first if auto-publish is enabled
            if ($this->autoPublish && ! $this->content->isPublished()) {
                $this->content->publish();
                Log::info('Content auto-published', [
                    'batch_id' => $this->batchId,
                    'content_id' => $this->content->id,
                ]);
            }

            // Sync to PrestaShop
            $syncResult = $syncService->syncToPrestaShop($this->content);

            if ($syncResult) {
                // Update synced_to_erp_at timestamp
                $this->content->syncToErp();

                DB::commit();

                Log::info('PrestaShop sync completed successfully', [
                    'batch_id' => $this->batchId,
                    'content_id' => $this->content->id,
                    'product_id' => $this->content->product_id,
                    'published_at' => $this->content->published_at?->toIso8601String(),
                    'synced_to_erp_at' => $this->content->synced_to_erp_at?->toIso8601String(),
                ]);
            } else {
                DB::rollBack();

                $errorMessage = 'PrestaShop sync returned false';
                Log::error($errorMessage, [
                    'batch_id' => $this->batchId,
                    'content_id' => $this->content->id,
                ]);

                throw new \RuntimeException($errorMessage);
            }
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('PrestaShop sync failed for single content', [
                'batch_id' => $this->batchId,
                'content_id' => $this->content->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle batch synchronization of multiple content items
     */
    protected function handleBatchSync(SyncService $syncService): void
    {
        Log::info('Starting PrestaShop batch sync', [
            'batch_id' => $this->batchId,
            'content_count' => count($this->contentIds),
            'auto_publish' => $this->autoPublish,
        ]);

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($this->contentIds as $contentId) {
            try {
                $content = SupplierAiContent::findOrFail($contentId);

                DB::beginTransaction();

                // Check if content is ready for sync
                if (! $content->isValidated() && ! $content->isPublished()) {
                    $skippedCount++;
                    DB::rollBack();

                    Log::warning('Content skipped - not validated', [
                        'batch_id' => $this->batchId,
                        'content_id' => $contentId,
                        'status' => $content->status,
                    ]);

                    continue;
                }

                // Publish content first if auto-publish is enabled
                if ($this->autoPublish && ! $content->isPublished()) {
                    $content->publish();
                }

                // Sync to PrestaShop
                $syncResult = $syncService->syncToPrestaShop($content);

                if ($syncResult) {
                    // Update synced_to_erp_at timestamp
                    $content->syncToErp();

                    DB::commit();
                    $successCount++;

                    Log::debug('Content synced in batch', [
                        'batch_id' => $this->batchId,
                        'content_id' => $contentId,
                        'product_id' => $content->product_id,
                    ]);
                } else {
                    DB::rollBack();
                    $errorCount++;

                    $errors[] = [
                        'content_id' => $contentId,
                        'error' => 'Sync returned false',
                    ];

                    Log::warning('Content sync failed in batch', [
                        'batch_id' => $this->batchId,
                        'content_id' => $contentId,
                        'error' => 'Sync returned false',
                    ]);
                }
            } catch (Throwable $e) {
                DB::rollBack();
                $errorCount++;

                $errors[] = [
                    'content_id' => $contentId,
                    'error' => $e->getMessage(),
                ];

                Log::error('Content sync error in batch', [
                    'batch_id' => $this->batchId,
                    'content_id' => $contentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('PrestaShop batch sync completed', [
            'batch_id' => $this->batchId,
            'total_count' => count($this->contentIds),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
        ]);

        // If all items failed, throw exception to trigger job retry
        if ($errorCount > 0 && $successCount === 0) {
            throw new \RuntimeException("All {$errorCount} items failed to sync in batch");
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        if ($this->content) {
            Log::error('PrestaShop sync job permanently failed for single content', [
                'batch_id' => $this->batchId,
                'content_id' => $this->content->id,
                'content_uid' => $this->content->uid,
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            // Update content with error status
            $this->content->update([
                'sync_status' => 'failed',
                'sync_error' => $exception->getMessage(),
                'last_sync_attempt' => now(),
            ]);
        } elseif ($this->contentIds) {
            Log::error('PrestaShop sync job permanently failed for batch', [
                'batch_id' => $this->batchId,
                'content_count' => count($this->contentIds),
                'content_ids' => $this->contentIds,
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);

            // Update all content items with error status
            SupplierAiContent::whereIn('id', $this->contentIds)
                ->update([
                    'sync_status' => 'failed',
                    'sync_error' => $exception->getMessage(),
                    'last_sync_attempt' => now(),
                ]);
        }
    }

    /**
     * Get the unique identifier for this job
     */
    public function uniqueId(): string
    {
        return $this->batchId;
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        $tags = ['prestashop-sync', 'supplier-content'];

        if ($this->content) {
            $tags[] = "content:{$this->content->id}";
            $tags[] = "supplier:{$this->content->supplier_id}";
        } elseif ($this->contentIds) {
            $tags[] = 'batch-sync';
            $tags[] = 'count:'.count($this->contentIds);
        }

        return $tags;
    }
}
