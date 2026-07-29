<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncStatus;

/**
 * Abstract base class for all synchronization agents
 *
 * Provides shared sync logic and lifecycle management for concrete sync agents.
 * Each agent handles a specific sync type (product, category, price, provider).
 *
 * Features:
 * - Manages batch/status lifecycle
 * - Integrates with SyncStatusService
 * - Supports cancellation via cache flag
 * - Reports progress to Redis and Database
 * - Comprehensive error handling and logging
 * - Automatic cleanup on completion
 */
abstract class BaseSyncAgent
{
    protected SyncBatch $batch;

    protected SyncStatus $status;

    protected SyncStatusService $syncStatusService;

    protected int $itemsProcessed = 0;

    protected int $itemsFailed = 0;

    protected int $itemsSkipped = 0;

    protected int $currentBatchIndex = 0;

    protected int $totalBatches = 0;

    public function __construct(
        SyncBatch $batch,
        SyncStatusService $syncStatusService
    ) {
        $this->batch = $batch;
        $this->syncStatusService = $syncStatusService;
    }

    /**
     * Execute the synchronization
     *
     * This is the main entry point that orchestrates the sync process.
     * Concrete agents must implement specific sync logic.
     *
     * @return array{
     *     success: bool,
     *     items_processed: int,
     *     items_failed: int,
     *     items_skipped: int,
     *     message: string
     * }
     */
    abstract public function execute(): array;

    /**
     * Get the sync type this agent handles
     */
    abstract protected function getSyncType(): string;

    /**
     * Fetch the items to be synced
     *
     * Must return an iterable collection of items for processing.
     */
    abstract protected function fetchItems(): iterable;

    /**
     * Process a single item
     *
     * Concrete implementation of item processing logic.
     * Must return true on success, false on failure.
     */
    abstract protected function processItem(mixed $item): bool;

    /**
     * Initialize the sync operation
     *
     * Sets up batch processing and creates initial status record.
     */
    protected function initializeSync(int $totalItems, string $triggeredBy = 'manual'): SyncStatus
    {
        try {
            $this->status = $this->syncStatusService->startSync(
                batch: $this->batch,
                totalItems: $totalItems,
                triggeredBy: $triggeredBy,
                metadata: [
                    'sync_type' => $this->getSyncType(),
                    'batch_name' => $this->batch->batch_name,
                    'batch_size' => $this->batch->batch_size,
                ]
            );

            Log::info('Sync agent initialized', [
                'agent_type' => static::class,
                'batch_id' => $this->batch->id,
                'total_items' => $totalItems,
            ]);

            return $this->status;
        } catch (Exception $e) {
            Log::error('Failed to initialize sync', [
                'agent_type' => static::class,
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get current progress of the sync
     *
     * Returns progress metrics as array.
     *
     * @return array{
     *     total_items: int,
     *     items_processed: int,
     *     items_failed: int,
     *     items_skipped: int,
     *     progress_percentage: float,
     *     current_batch: int,
     *     total_batches: int,
     *     is_cancelled: bool
     * }
     */
    public function getProgress(): array
    {
        $totalItems = $this->status->total_items ?? 0;
        $processed = $this->itemsProcessed + $this->itemsFailed + $this->itemsSkipped;
        $progressPercentage = $totalItems > 0 ? ($processed / $totalItems) * 100 : 0;

        return [
            'total_items' => $totalItems,
            'items_processed' => $this->itemsProcessed,
            'items_failed' => $this->itemsFailed,
            'items_skipped' => $this->itemsSkipped,
            'progress_percentage' => round($progressPercentage, 2),
            'current_batch' => $this->currentBatchIndex,
            'total_batches' => $this->totalBatches,
            'is_cancelled' => $this->isCancellationRequested(),
        ];
    }

    /**
     * Check if cancellation has been requested
     *
     * Checks Redis cache for cancellation flag.
     */
    protected function isCancellationRequested(): bool
    {
        return $this->syncStatusService->isCancellationRequested($this->batch->id);
    }

    /**
     * Report progress to SyncStatusService
     *
     * Updates both Redis cache and database.
     */
    protected function reportProgress(): void
    {
        try {
            $this->syncStatusService->updateProgress(
                status: $this->status,
                syncedCount: $this->itemsProcessed,
                failedCount: $this->itemsFailed,
                skippedCount: $this->itemsSkipped
            );
        } catch (Exception $e) {
            Log::warning('Failed to report progress', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process items in batches with progress reporting
     *
     * Handles batch processing with automatic progress updates.
     */
    protected function processBatch(iterable $items, int $batchSize = 100): bool
    {
        try {
            $batch = [];
            $batchIndex = 0;

            foreach ($items as $item) {
                $batch[] = $item;

                if (count($batch) >= $batchSize) {
                    $batchIndex++;
                    $this->totalBatches = $batchIndex;

                    if (! $this->processBatchItems($batch, $batchIndex)) {
                        return false; // Cancellation requested or critical error
                    }

                    $batch = [];
                    $this->reportProgress();
                }
            }

            // Process remaining items
            if (! empty($batch)) {
                $batchIndex++;
                $this->totalBatches = $batchIndex;

                return $this->processBatchItems($batch, $batchIndex);
            }

            return true;
        } catch (Exception $e) {
            $this->handleError(
                message: 'Error during batch processing',
                exception: $e,
                context: [
                    'current_batch' => $this->currentBatchIndex,
                    'total_batches' => $this->totalBatches,
                ]
            );

            return false;
        }
    }

    /**
     * Process a single batch of items
     */
    protected function processBatchItems(array $items, int $batchIndex): bool
    {
        $this->currentBatchIndex = $batchIndex;

        foreach ($items as $item) {
            // Check for cancellation before each item
            if ($this->isCancellationRequested()) {
                Log::info('Sync cancelled by user', [
                    'batch_id' => $this->batch->id,
                    'item_index' => $this->itemsProcessed + $this->itemsFailed + $this->itemsSkipped,
                ]);

                return false;
            }

            try {
                if ($this->processItem($item)) {
                    $this->itemsProcessed++;
                } else {
                    $this->itemsFailed++;
                }
            } catch (Exception $e) {
                Log::warning('Error processing item', [
                    'batch_id' => $this->batch->id,
                    'error' => $e->getMessage(),
                ]);

                $this->itemsFailed++;
            }
        }

        $this->batch->incrementProcessedBatches();

        return true;
    }

    /**
     * Mark an item as skipped
     */
    protected function skipItem(string $reason = 'No changes detected'): void
    {
        $this->itemsSkipped++;

        Log::debug('Item skipped', [
            'batch_id' => $this->batch->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Log a sync action
     *
     * Records action in database for audit trail.
     */
    protected function logAction(
        string $entityType,
        ?int $entityId,
        string $action,
        string $result,
        ?string $message = null,
        ?array $dataBefore = null,
        ?array $dataAfter = null,
        ?array $changes = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        int $durationMs = 0
    ): void {
        try {
            $this->syncStatusService->logAction(
                batch: $this->batch,
                entityType: $entityType,
                entityId: $entityId,
                action: $action,
                result: $result,
                message: $message,
                dataBefore: $dataBefore,
                dataAfter: $dataAfter,
                changes: $changes,
                errorCode: $errorCode,
                errorMessage: $errorMessage,
                durationMs: $durationMs
            );
        } catch (Exception $e) {
            Log::warning('Failed to log action', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle sync error
     *
     * Records error, updates status, and manages failure recovery.
     */
    public function handleError(
        string $message,
        Exception $exception,
        ?array $context = null
    ): void {
        Log::error($message, array_merge([
            'agent_type' => static::class,
            'batch_id' => $this->batch->id,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $context ?? []));
    }

    /**
     * Complete the sync operation successfully
     *
     * Finalizes status, updates metrics, and cleans up.
     */
    protected function completeSync(?array $additionalMetadata = null): SyncStatus
    {
        $metadata = [
            'items_processed' => $this->itemsProcessed,
            'items_failed' => $this->itemsFailed,
            'items_skipped' => $this->itemsSkipped,
            'total_batches' => $this->totalBatches,
        ];

        if ($additionalMetadata) {
            $metadata = array_merge($metadata, $additionalMetadata);
        }

        $this->status = $this->syncStatusService->completeSync(
            status: $this->status,
            finalMetadata: $metadata
        );

        Log::info('Sync completed by agent', [
            'agent_type' => static::class,
            'batch_id' => $this->batch->id,
            'items_processed' => $this->itemsProcessed,
            'items_failed' => $this->itemsFailed,
            'items_skipped' => $this->itemsSkipped,
        ]);

        return $this->status;
    }

    /**
     * Fail the sync operation
     *
     * Marks status as failed and records failure reason.
     */
    protected function failSync(
        string $failureReason,
        ?string $failureCode = null
    ): SyncStatus {
        $this->status = $this->syncStatusService->failSync(
            status: $this->status,
            failureReason: $failureReason,
            failureCode: $failureCode
        );

        Log::warning('Sync failed by agent', [
            'agent_type' => static::class,
            'batch_id' => $this->batch->id,
            'reason' => $failureReason,
            'code' => $failureCode,
        ]);

        return $this->status;
    }

    /**
     * Record a failure for retry processing
     *
     * Creates failure record that can be retried later.
     */
    protected function recordFailure(
        string $syncType,
        int $supplierId,
        ?int $entityId,
        ?int $erpId,
        array $changedData,
        string $errorMessage,
        ?string $errorCode = null,
        ?array $context = null,
        int $maxRetries = 3
    ): void {
        try {
            $this->syncStatusService->recordFailure(
                batch: $this->batch,
                syncType: $syncType,
                supplierId: $supplierId,
                entityId: $entityId,
                erpId: $erpId,
                changedData: $changedData,
                errorMessage: $errorMessage,
                errorCode: $errorCode,
                context: $context,
                maxRetries: $maxRetries
            );
        } catch (Exception $e) {
            Log::error('Failed to record failure', [
                'batch_id' => $this->batch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the associated batch
     */
    public function getBatch(): SyncBatch
    {
        return $this->batch;
    }

    /**
     * Get the associated status
     */
    public function getStatus(): ?SyncStatus
    {
        return $this->status ?? null;
    }

    /**
     * Get the sync status service
     */
    public function getSyncStatusService(): SyncStatusService
    {
        return $this->syncStatusService;
    }
}
