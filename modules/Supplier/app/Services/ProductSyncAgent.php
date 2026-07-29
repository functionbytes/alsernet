<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * ProductSyncAgent - Synchronizes Product entities with ERP
 *
 * Handles synchronization of product data including:
 * - Product code/reference mapping to ERP
 * - Product status flags and metadata
 * - Pricing information validation
 * - Product hierarchy (groups/categories)
 * - Conflict detection and resolution
 *
 * Sync Strategy:
 * - Fetches unsynced or outdated products
 * - Validates product data before sync
 * - Updates ERP via ErpSyncService
 * - Records sync actions for audit trail
 * - Handles failures with retry capability
 */
class ProductSyncAgent extends BaseSyncAgent
{
    /**
     * Fields mapped to Oracle by ErpSyncService::syncProductToOracle().
     * Used as the changed-fields payload when a product has no pending dirty changes.
     */
    private const SYNCABLE_FIELDS = ['name', 'barcode', 'recommended_price', 'is_active'];

    private ErpSyncService $erpSyncService;

    private int $supplierId = 0;

    private ?string $dateRangeStart = null;

    private ?string $dateRangeEnd = null;

    public function __construct(
        SyncBatch $batch,
        SyncStatusService $syncStatusService,
        ErpSyncService $erpSyncService
    ) {
        parent::__construct($batch, $syncStatusService);
        $this->erpSyncService = $erpSyncService;
    }

    /**
     * Set supplier ID filter for this sync
     */
    public function forSupplier(int $supplierId): self
    {
        $this->supplierId = $supplierId;

        return $this;
    }

    /**
     * Set date range filter for sync
     */
    public function withinDateRange(?string $start, ?string $end): self
    {
        $this->dateRangeStart = $start;
        $this->dateRangeEnd = $end;

        return $this;
    }

    /**
     * Execute the product synchronization
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
            // Count items first (separate from cursor to avoid consuming it)
            $itemCount = $this->buildQuery()->count();

            $this->initializeSync(
                totalItems: $itemCount,
                triggeredBy: $this->batch->triggered_by ?? 'manual'
            );

            if ($itemCount === 0) {
                Log::info('No products to sync', [
                    'supplier_id' => $this->supplierId,
                    'sync_type' => 'product',
                ]);

                return $this->completeSync([
                    'reason' => 'No products matched sync criteria',
                ])->toArray();
            }

            // Now fetch items as cursor for streaming processing
            $items = $this->fetchItems();

            // Process items in batches
            if (! $this->processBatch($items, $this->batch->batch_size)) {
                return $this->failSync(
                    failureReason: 'Product sync was cancelled or encountered critical error',
                    failureCode: 'SYNC_CANCELLED'
                )->toArray();
            }

            // Complete sync successfully
            return $this->completeSync([
                'supplier_id' => $this->supplierId,
                'date_range' => [
                    'start' => $this->dateRangeStart,
                    'end' => $this->dateRangeEnd,
                ],
            ])->toArray();
        } catch (Exception $e) {
            $this->handleError(
                message: 'Critical error in product sync',
                exception: $e,
                context: [
                    'supplier_id' => $this->supplierId,
                    'date_range_start' => $this->dateRangeStart,
                    'date_range_end' => $this->dateRangeEnd,
                ]
            );

            return $this->failSync(
                failureReason: $e->getMessage(),
                failureCode: 'PRODUCT_SYNC_ERROR'
            )->toArray();
        }
    }

    /**
     * Get the sync type identifier
     */
    protected function getSyncType(): string
    {
        return 'product';
    }

    /**
     * Build the base query used for both counting and fetching items.
     */
    private function buildQuery(): Builder
    {
        $query = Product::query()
            ->with(['supplier', 'category'])
            ->where('available', true);

        if ($this->supplierId > 0) {
            $query->where('supplier_id', $this->supplierId);
        }

        if ($this->dateRangeStart) {
            $query->where('updated_at', '>=', $this->dateRangeStart);
        }

        if ($this->dateRangeEnd) {
            $query->where('updated_at', '<=', $this->dateRangeEnd);
        }

        $query->where(function ($q) {
            $q->whereNull('last_sync_at')
                ->orWhereRaw('last_sync_at < updated_at');
        });

        return $query->orderByDesc('updated_at')->orderBy('id');
    }

    /**
     * Fetch all products that need synchronization as a streaming cursor.
     *
     * @return iterable<Product>
     */
    protected function fetchItems(): iterable
    {
        try {
            return $this->buildQuery()->cursor();
        } catch (Exception $e) {
            Log::error('Failed to fetch products for sync', [
                'supplier_id' => $this->supplierId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single product for synchronization
     *
     * Validates product data, syncs to ERP, and records the action.
     *
     * @param  SupplierProduct  $product  The product to sync
     * @return bool True if sync succeeded, false otherwise
     */
    protected function processItem(mixed $product): bool
    {
        $startTime = microtime(true);

        try {
            if (! $product instanceof Product) {
                throw new Exception('Invalid item type: expected Product');
            }

            if (! $product->erp_id) {
                $this->skipItem('Product has no ERP ID');
                $this->logAction(entityType: 'product', entityId: $product->id, action: 'sync', result: 'skipped', message: 'No ERP ID');

                return false;
            }

            $this->erpSyncService->syncProductToOracle($product, $this->changedFieldsFor($product));

            $product->update(['last_sync_at' => now()]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logAction(
                entityType: 'product',
                entityId: $product->id,
                action: 'sync',
                result: 'success',
                message: "Product synced: {$product->name}",
                durationMs: $durationMs
            );

            return true;
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::warning('Failed to sync product', ['product_id' => $product->id, 'error' => $e->getMessage()]);

            $this->recordFailure(
                syncType: 'product',
                supplierId: $product->supplier_id ?? 0,
                entityId: $product->id,
                erpId: $product->erp_id,
                changedData: [],
                errorMessage: $e->getMessage(),
                errorCode: 'PRODUCT_SYNC_FAILED',
                context: ['code' => $product->code, 'name' => $product->name],
                maxRetries: 3
            );

            $this->logAction(
                entityType: 'product',
                entityId: $product->id,
                action: 'sync',
                result: 'failed',
                message: "Failed to sync product: {$product->name}",
                errorCode: 'PRODUCT_SYNC_FAILED',
                errorMessage: $e->getMessage(),
                durationMs: $durationMs
            );

            return false;
        }
    }

    /**
     * Determine which fields to push to Oracle for the given product.
     *
     * Uses the product's pending dirty attributes when present, otherwise falls
     * back to the full set of syncable fields so the ERP record stays consistent.
     *
     * @return list<string>
     */
    private function changedFieldsFor(Product $product): array
    {
        $dirty = array_keys($product->getDirty());

        if ($dirty === []) {
            return self::SYNCABLE_FIELDS;
        }

        return array_values(array_intersect($dirty, self::SYNCABLE_FIELDS)) ?: self::SYNCABLE_FIELDS;
    }
}
