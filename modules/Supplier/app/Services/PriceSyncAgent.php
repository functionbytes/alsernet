<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * PriceSyncAgent - Synchronizes SupplierProductPrice entities with ERP
 *
 * Handles synchronization of product pricing including:
 * - Price history and versioning
 * - Discount calculations and validation
 * - Current vs historical price management
 * - Conflict detection (ERP takes precedence)
 * - Price effective date tracking
 * - Cost and final price calculations
 *
 * Sync Strategy:
 * - Fetches prices by date range, supplier, or changed flag
 * - Validates price calculations
 * - Detects conflicts with ERP (ERP wins)
 * - Syncs valid price changes to ERP
 * - Maintains price history integrity
 * - Records all price change actions
 */
class PriceSyncAgent extends BaseSyncAgent
{
    private ErpSyncService $erpSyncService;

    private int $supplierId = 0;

    private ?string $dateRangeStart = null;

    private ?string $dateRangeEnd = null;

    private bool $currentPricesOnly = false;

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
     * Set date range filter for price effective dates
     */
    public function withinDateRange(?string $start, ?string $end): self
    {
        $this->dateRangeStart = $start;
        $this->dateRangeEnd = $end;

        return $this;
    }

    /**
     * Sync only current prices (is_current = true)
     */
    public function currentPricesOnly(bool $value): self
    {
        $this->currentPricesOnly = $value;

        return $this;
    }

    /**
     * Execute the price synchronization
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
                Log::info('No product prices to sync', [
                    'supplier_id' => $this->supplierId,
                    'sync_type' => 'price',
                    'current_only' => $this->currentPricesOnly,
                ]);

                return $this->completeSync([
                    'reason' => 'No product prices matched sync criteria',
                ])->toArray();
            }

            $items = $this->fetchItems();

            if (! $this->processBatch($items, $this->batch->batch_size)) {
                return $this->failSync(
                    failureReason: 'Price sync was cancelled or encountered critical error',
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
                'current_prices_only' => $this->currentPricesOnly,
            ])->toArray();
        } catch (Exception $e) {
            $this->handleError(
                message: 'Critical error in price sync',
                exception: $e,
                context: [
                    'supplier_id' => $this->supplierId,
                    'date_range_start' => $this->dateRangeStart,
                    'date_range_end' => $this->dateRangeEnd,
                ]
            );

            return $this->failSync(
                failureReason: $e->getMessage(),
                failureCode: 'PRICE_SYNC_ERROR'
            )->toArray();
        }
    }

    /**
     * Get the sync type identifier
     */
    protected function getSyncType(): string
    {
        return 'price';
    }

    /**
     * Build the base query used for counting and fetching prices.
     */
    private function buildQuery(): Builder
    {
        $query = SupplierProductPrice::query()
            ->with(['providerProduct.provider', 'providerProduct.product'])
            ->where('is_active', true);

        if ($this->supplierId > 0) {
            $query->whereHas('providerProduct.provider', function ($q) {
                $q->where('supplier_id', $this->supplierId);
            });
        }

        if ($this->currentPricesOnly) {
            $query->where('is_current', true);
        }

        if ($this->dateRangeStart) {
            $query->where('effective_date', '>=', $this->dateRangeStart);
        }

        if ($this->dateRangeEnd) {
            $query->where('effective_date', '<=', $this->dateRangeEnd);
        }

        $query->where(function ($q) {
            $q->whereNull('last_synced_at')
                ->orWhereRaw('erp_updated_at > last_synced_at');
        });

        return $query->orderByDesc('erp_updated_at')->orderBy('id');
    }

    /**
     * Fetch all prices that need synchronization as a streaming cursor.
     *
     * @return iterable<SupplierProductPrice>
     */
    protected function fetchItems(): iterable
    {
        try {
            return $this->buildQuery()->cursor();
        } catch (Exception $e) {
            Log::error('Failed to fetch product prices for sync', [
                'supplier_id' => $this->supplierId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single price for synchronization
     *
     * Validates price data, detects conflicts, syncs to ERP, and records the action.
     *
     * @param  SupplierProductPrice  $price  The price to sync
     * @return bool True if sync succeeded, false otherwise
     */
    protected function processItem(mixed $price): bool
    {
        $startTime = microtime(true);

        try {
            // Cast to ensure type safety
            if (! $price instanceof SupplierProductPrice) {
                throw new Exception('Invalid item type: expected SupplierProductPrice');
            }

            // Load relationships if not already loaded
            if (! $price->relationLoaded('providerProduct')) {
                $price->load(['providerProduct.provider', 'providerProduct.product']);
            }

            // Skip if price has no ERP mapping
            if (! $price->erp_price_id || ! $price->erp_artiprov_id) {
                $this->skipItem('Price has no ERP ID mapping');

                $this->logAction(
                    entityType: 'product_price',
                    entityId: $price->id,
                    action: 'sync',
                    result: 'skipped',
                    message: 'No ERP ID mapping found'
                );

                return false;
            }

            // Validate price data
            if (! $this->validatePrice($price)) {
                throw new Exception('Price validation failed');
            }

            // Recalculate final cost to ensure consistency
            $calculatedFinalCost = $price->calculateFinalCost();
            if (abs($calculatedFinalCost - $price->final_cost) > 0.01) {
                Log::debug('Price final_cost mismatch detected, updating', [
                    'price_id' => $price->id,
                    'stored_final_cost' => $price->final_cost,
                    'calculated_final_cost' => $calculatedFinalCost,
                ]);

                $price->update(['final_cost' => $calculatedFinalCost]);
            }

            // Detect changes from last sync
            $changedFields = $this->detectChanges($price);

            if (empty($changedFields)) {
                $this->skipItem('No changes detected since last sync');

                $this->logAction(
                    entityType: 'product_price',
                    entityId: $price->id,
                    action: 'sync',
                    result: 'skipped',
                    message: 'No changes since last sync'
                );

                return false;
            }

            // Sync to ERP
            $this->erpSyncService->syncPriceToOracle($price, $changedFields);

            // Update sync timestamp
            $price->update(['last_synced_at' => now()]);

            // Log successful action
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $productName = $price->providerProduct?->product?->name ?? 'Unknown';

            $this->logAction(
                entityType: 'product_price',
                entityId: $price->id,
                action: 'sync',
                result: 'success',
                message: "Price synced successfully: {$productName}",
                dataAfter: [
                    'cost' => $price->cost,
                    'discount1' => $price->discount1,
                    'discount2' => $price->discount2,
                    'final_cost' => $price->final_cost,
                    'is_current' => $price->is_current,
                ],
                changes: $changedFields,
                durationMs: $durationMs
            );

            Log::debug('Product price synced successfully', [
                'price_id' => $price->id,
                'erp_id' => $price->erp_price_id,
                'cost' => $price->cost,
                'final_cost' => $price->final_cost,
                'changed_fields' => $changedFields,
                'duration_ms' => $durationMs,
            ]);

            return true;
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::warning('Failed to sync product price', [
                'price_id' => $price->id,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            // Record failure for retry processing
            $providerProduct = $price->providerProduct;
            $this->recordFailure(
                syncType: 'price',
                supplierId: $providerProduct->provider->supplier_id ?? 0,
                entityId: $price->id,
                erpId: $price->erp_price_id,
                changedData: [
                    'cost' => $price->cost,
                    'discount1' => $price->discount1,
                    'discount2' => $price->discount2,
                    'final_cost' => $price->final_cost,
                    'effective_date' => $price->effective_date,
                ],
                errorMessage: $e->getMessage(),
                errorCode: 'PRICE_SYNC_FAILED',
                context: [
                    'product_name' => $providerProduct->product->name ?? 'Unknown',
                    'provider_code' => $providerProduct->provider_code,
                ],
                maxRetries: 3
            );

            // Log failure action
            $this->logAction(
                entityType: 'product_price',
                entityId: $price->id,
                action: 'sync',
                result: 'failed',
                message: 'Failed to sync product price',
                errorCode: 'PRICE_SYNC_FAILED',
                errorMessage: $e->getMessage(),
                durationMs: $durationMs
            );

            return false;
        }
    }

    /**
     * Validate price data before sync
     *
     * Checks required fields, cost validity, and data consistency.
     */
    private function validatePrice(SupplierProductPrice $price): bool
    {
        // Check required fields
        if (! isset($price->cost)) {
            Log::warning('Price has no cost value', [
                'price_id' => $price->id,
            ]);

            return false;
        }

        // Check valid numeric cost
        if ($price->cost < 0) {
            Log::warning('Price has negative cost', [
                'price_id' => $price->id,
                'cost' => $price->cost,
            ]);

            return false;
        }

        // Validate provider-product relationship
        if (! $price->provider_product_id) {
            Log::warning('Price has no provider-product relationship', [
                'price_id' => $price->id,
            ]);

            return false;
        }

        // Validate discount values
        if ($price->discount1 < 0 || $price->discount1 > 100) {
            Log::warning('Price has invalid discount1', [
                'price_id' => $price->id,
                'discount1' => $price->discount1,
            ]);

            return false;
        }

        if ($price->discount2 < 0 || $price->discount2 > 100) {
            Log::warning('Price has invalid discount2', [
                'price_id' => $price->id,
                'discount2' => $price->discount2,
            ]);

            return false;
        }

        // Validate effective date
        if (! $price->effective_date) {
            Log::warning('Price has no effective date', [
                'price_id' => $price->id,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Detect which fields have changed since last sync
     *
     * Compares current price data with ERP's last known state
     * to identify what needs to be synced.
     *
     * @return array List of field names that changed
     */
    private function detectChanges(SupplierProductPrice $price): array
    {
        $changedFields = [];

        if ($price->isDirty('cost')) {
            $changedFields[] = 'cost';
        }

        if ($price->isDirty('discount1')) {
            $changedFields[] = 'discount1';
        }

        if ($price->isDirty('discount2')) {
            $changedFields[] = 'discount2';
        }

        if ($price->isDirty('effective_date')) {
            $changedFields[] = 'effective_date';
        }

        if ($price->isDirty('is_active')) {
            $changedFields[] = 'is_active';
        }

        if ($price->isDirty('is_current')) {
            $changedFields[] = 'is_current';
        }

        return array_unique($changedFields);
    }

    /**
     * Compare current price with ERP price for conflict detection
     *
     * Returns true if a conflict exists (ERP was modified more recently).
     */
    private function hasConflict(SupplierProductPrice $price, $erpPrice): bool
    {
        if (! $price->last_synced_at || ! $erpPrice->erp_updated_at) {
            return false;
        }

        return $erpPrice->erp_updated_at->isAfter($price->last_synced_at);
    }
}
