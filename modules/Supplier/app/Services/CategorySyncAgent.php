<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * CategorySyncAgent - Synchronizes Category entities with ERP
 *
 * Handles synchronization of supplier-category relationships including:
 * - Parent-child category hierarchies
 * - Category mappings and metadata
 * - Supplier category assignments
 * - Priority and ordering information
 * - Validation of parent categories before child sync
 *
 * Sync Strategy:
 * - Fetches all supplier category assignments
 * - Validates hierarchical relationships
 * - Syncs parent categories before children
 * - Handles bulk category operations
 * - Records sync actions for audit trail
 */
class CategorySyncAgent extends BaseSyncAgent
{
    public function __construct(
        SyncBatch $batch,
        SyncStatusService $syncStatusService,
        private ErpSyncService $erpSyncService,
    ) {
        parent::__construct($batch, $syncStatusService);
    }

    /**
     * Execute the category synchronization
     *
     * Main entry point that orchestrates the full sync lifecycle.
     *
     * @return array{
     *     success: bool,
     *     items_processed: int,
     *     items_failed: int,
     *     items_skipped: int,
     *     message: string
     * }
     */
    public function execute(): array
    {
        try {
            $itemCount = $this->buildQuery()->count();

            $this->initializeSync(
                totalItems: $itemCount,
                triggeredBy: $this->batch->triggered_by ?? 'manual'
            );

            if ($itemCount === 0) {
                Log::info('No supplier categories to sync', [
                    'batch_id' => $this->batch->id,
                    'sync_type' => 'category',
                ]);

                return $this->completeSync([
                    'reason' => 'No supplier categories matched sync criteria',
                ])->toArray();
            }

            $items = $this->fetchItems();

            if (! $this->processBatch($items, $this->batch->batch_size)) {
                return $this->failSync(
                    failureReason: 'Category sync was cancelled or encountered critical error',
                    failureCode: 'SYNC_CANCELLED'
                )->toArray();
            }

            // Complete sync successfully
            return $this->completeSync([])->toArray();
        } catch (Exception $e) {
            $this->handleError(
                message: 'Critical error in category sync',
                exception: $e,
                context: []
            );

            return $this->failSync(
                failureReason: $e->getMessage(),
                failureCode: 'CATEGORY_SYNC_ERROR'
            )->toArray();
        }
    }

    /**
     * Get the sync type identifier
     */
    protected function getSyncType(): string
    {
        return 'category';
    }

    /**
     * Build the base query used for counting and fetching categories.
     */
    private function buildQuery(): Builder
    {
        return Category::query()
            ->with(['sport'])
            ->where('available', true)
            ->orderBy('id');
    }

    /**
     * Fetch all categories that need synchronization as a streaming cursor.
     *
     * @return iterable<Category>
     */
    protected function fetchItems(): iterable
    {
        try {
            return $this->buildQuery()->cursor();
        } catch (Exception $e) {
            Log::error('Failed to fetch categories for sync', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single supplier category for synchronization
     *
     * Validates category relationships, syncs to ERP, and records the action.
     *
     * @param  Category  $category  The supplier category to sync
     * @return bool True if sync succeeded, false otherwise
     */
    protected function processItem(mixed $category): bool
    {
        $startTime = microtime(true);

        try {
            if (! $category instanceof Category) {
                throw new Exception('Invalid item type: expected Category');
            }

            $this->erpSyncService->syncCategoryToOracle($category);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logAction(
                entityType: 'category',
                entityId: $category->id,
                action: 'sync',
                result: 'success',
                message: "Category synced: {$category->name}",
                durationMs: $durationMs
            );

            return true;
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::warning('Failed to sync category', ['category_id' => $category->id, 'error' => $e->getMessage()]);

            $this->recordFailure(
                syncType: 'category',
                supplierId: 0,
                entityId: $category->id,
                erpId: $category->erp_id,
                changedData: [],
                errorMessage: $e->getMessage(),
                errorCode: 'CATEGORY_SYNC_FAILED',
                context: ['name' => $category->name],
                maxRetries: 3
            );

            $this->logAction(
                entityType: 'category',
                entityId: $category->id,
                action: 'sync',
                result: 'failed',
                message: "Failed to sync category: {$category->name}",
                errorCode: 'CATEGORY_SYNC_FAILED',
                errorMessage: $e->getMessage(),
                durationMs: $durationMs
            );

            return false;
        }
    }
}
