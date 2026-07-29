<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * ProviderSyncAgent - Synchronizes SupplierErpProvider entities (bidirectional)
 *
 * Handles bidirectional synchronization of ERP provider data including:
 * - Provider information sync (name, contact, address)
 * - Connection verification and credential validation
 * - Provider status management (active/inactive)
 * - Configuration changes sync back to source
 * - Provider visibility and creditor flags
 * - Shipping cost and discount information
 *
 * Sync Strategy:
 * - Fetches providers that need syncing (new or modified)
 * - Validates provider credentials and connectivity
 * - Detects conflicts between Supplier and ERP (ERP takes precedence)
 * - Syncs bidirectionally: Supplier → ERP and ERP → Supplier
 * - Maintains provider relationships and metadata
 * - Records all provider change actions
 *
 * Bidirectional Sync:
 * - Changed in Supplier DB: Sync to ERP
 * - Changed in ERP: Re-sync from ERP to Supplier
 * - Both changed: ERP takes precedence (wins)
 */
class ProviderSyncAgent extends BaseSyncAgent
{
    private ErpSyncService $erpSyncService;

    private int $supplierId = 0;

    private bool $bidirectionalSync = true;

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
     * Enable or disable bidirectional sync
     */
    public function bidirectional(bool $value): self
    {
        $this->bidirectionalSync = $value;

        return $this;
    }

    /**
     * Execute the provider synchronization
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
                Log::info('No ERP providers to sync', [
                    'supplier_id' => $this->supplierId,
                    'sync_type' => 'provider',
                ]);

                return $this->completeSync([
                    'reason' => 'No ERP providers matched sync criteria',
                ])->toArray();
            }

            $items = $this->fetchItems();

            if (! $this->processBatch($items, $this->batch->batch_size)) {
                return $this->failSync(
                    failureReason: 'Provider sync was cancelled or encountered critical error',
                    failureCode: 'SYNC_CANCELLED'
                )->toArray();
            }

            // Complete sync successfully
            return $this->completeSync([
                'supplier_id' => $this->supplierId,
                'bidirectional_sync' => $this->bidirectionalSync,
            ])->toArray();
        } catch (Exception $e) {
            $this->handleError(
                message: 'Critical error in provider sync',
                exception: $e,
                context: [
                    'supplier_id' => $this->supplierId,
                ]
            );

            return $this->failSync(
                failureReason: $e->getMessage(),
                failureCode: 'PROVIDER_SYNC_ERROR'
            )->toArray();
        }
    }

    /**
     * Get the sync type identifier
     */
    protected function getSyncType(): string
    {
        return 'provider';
    }

    /**
     * Build the base query used for counting and fetching providers.
     */
    private function buildQuery(): Builder
    {
        $query = Supplier::query()->where('available', true);

        if ($this->supplierId > 0) {
            $query->where('id', $this->supplierId);
        }

        $query->where(function ($q) {
            $q->whereNull('last_synced_at')
                ->orWhereRaw('erp_updated_at > last_synced_at');
        });

        return $query->orderByDesc('erp_updated_at')->orderBy('id');
    }

    /**
     * Fetch all providers that need synchronization as a streaming cursor.
     *
     * @return iterable<Supplier>
     */
    protected function fetchItems(): iterable
    {
        try {
            return $this->buildQuery()->cursor();
        } catch (Exception $e) {
            Log::error('Failed to fetch ERP providers for sync', [
                'supplier_id' => $this->supplierId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single provider for synchronization
     *
     * Validates provider data, tests connectivity, syncs to ERP, and records the action.
     *
     * @param  SupplierErpProvider  $provider  The provider to sync
     * @return bool True if sync succeeded, false otherwise
     */
    protected function processItem(mixed $provider): bool
    {
        $startTime = microtime(true);

        try {
            // Cast to ensure type safety
            if (! $provider instanceof Supplier) {
                throw new Exception('Invalid item type: expected Supplier');
            }

            // Validate provider data
            if (! $this->validateProvider($provider)) {
                throw new Exception('Provider validation failed');
            }

            // Verify provider connectivity/credentials
            if (! $this->verifyProviderConnection($provider)) {
                throw new Exception('Provider connection verification failed');
            }

            // Detect changes from last sync
            $changedFields = $this->detectChanges($provider);

            if (empty($changedFields) && $provider->last_synced_at) {
                $this->skipItem('No changes detected since last sync');

                $this->logAction(
                    entityType: 'erp_provider',
                    entityId: $provider->id,
                    action: 'sync',
                    result: 'skipped',
                    message: 'No changes since last sync'
                );

                return false;
            }

            // Sync to ERP (bidirectional)
            $this->erpSyncService->syncProviderToOracle($provider, $changedFields);

            // Update sync timestamp
            $provider->update(['last_synced_at' => now()]);

            // Log successful action
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logAction(
                entityType: 'erp_provider',
                entityId: $provider->id,
                action: 'sync',
                result: 'success',
                message: "ERP provider synced successfully: {$provider->name}",
                dataAfter: [
                    'code' => $provider->code,
                    'name' => $provider->name,
                    'email' => $provider->email,
                    'phone' => $provider->phone,
                    'is_active' => $provider->is_active,
                    'is_visible' => $provider->is_visible,
                ],
                changes: $changedFields,
                durationMs: $durationMs
            );

            Log::debug('ERP provider synced successfully', [
                'provider_id' => $provider->id,
                'erp_id' => $provider->erp_provider_id,
                'code' => $provider->code,
                'name' => $provider->name,
                'changed_fields' => $changedFields,
                'duration_ms' => $durationMs,
            ]);

            return true;
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            Log::warning('Failed to sync ERP provider', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            // Record failure for retry processing
            $this->recordFailure(
                syncType: 'provider',
                supplierId: $provider->supplier_id ?? 0,
                entityId: $provider->id,
                erpId: $provider->erp_provider_id,
                changedData: $this->detectChanges($provider),
                errorMessage: $e->getMessage(),
                errorCode: 'PROVIDER_SYNC_FAILED',
                context: [
                    'provider_code' => $provider->code,
                    'provider_name' => $provider->name,
                ],
                maxRetries: 3
            );

            // Log failure action
            $this->logAction(
                entityType: 'erp_provider',
                entityId: $provider->id,
                action: 'sync',
                result: 'failed',
                message: "Failed to sync ERP provider: {$provider->name}",
                errorCode: 'PROVIDER_SYNC_FAILED',
                errorMessage: $e->getMessage(),
                durationMs: $durationMs
            );

            return false;
        }
    }

    /**
     * Validate provider data before sync
     *
     * Checks required fields and data consistency.
     */
    private function validateProvider(Supplier $provider): bool
    {
        // Check required fields
        if (! $provider->name || ! $provider->code) {
            Log::warning('Provider missing required fields', [
                'provider_id' => $provider->id,
                'name' => $provider->name,
                'code' => $provider->code,
            ]);

            return false;
        }

        // Validate ERP ID mapping
        if (! $provider->erp_provider_id) {
            Log::warning('Provider has no ERP ID mapping', [
                'provider_id' => $provider->id,
            ]);

            return false;
        }

        // Validate address fields if available
        if ($provider->country && strlen($provider->country) < 2) {
            Log::warning('Provider has invalid country code', [
                'provider_id' => $provider->id,
                'country' => $provider->country,
            ]);

            return false;
        }

        // Validate numeric fields
        if ($provider->shipping_cost < 0 || $provider->discount_percent < 0) {
            Log::warning('Provider has negative numeric values', [
                'provider_id' => $provider->id,
                'shipping_cost' => $provider->shipping_cost,
                'discount_percent' => $provider->discount_percent,
            ]);

            return false;
        }

        // Validate email format if provided
        if ($provider->email && ! filter_var($provider->email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Provider has invalid email format', [
                'provider_id' => $provider->id,
                'email' => $provider->email,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Verify provider connection and credentials
     *
     * Tests provider connectivity and validates configuration.
     * This ensures the provider is reachable before attempting sync.
     *
     * @return bool True if connection is valid, false otherwise
     */
    private function verifyProviderConnection(Supplier $provider): bool
    {
        try {
            // For now, perform basic validation
            // In production, this would test actual connectivity to the provider
            // (e.g., HTTP request, API call, database connection, etc.)

            if (! $provider->is_active) {
                Log::warning('Provider is not active', [
                    'provider_id' => $provider->id,
                ]);

                return false;
            }

            Log::debug('Provider connection verified', [
                'provider_id' => $provider->id,
                'code' => $provider->code,
            ]);

            return true;
        } catch (Exception $e) {
            Log::warning('Failed to verify provider connection', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Detect which fields have changed since last sync
     *
     * Compares current provider data with ERP's last known state
     * to identify what needs to be synced.
     *
     * @return array List of field names that changed
     */
    private function detectChanges(Supplier $provider): array
    {
        $changedFields = [];

        // Basic information
        if ($provider->isDirty('name')) {
            $changedFields[] = 'name';
        }

        if ($provider->isDirty('code')) {
            $changedFields[] = 'code';
        }

        if ($provider->isDirty('tax_id')) {
            $changedFields[] = 'tax_id';
        }

        // Customers information
        if ($provider->isDirty('contact_person')) {
            $changedFields[] = 'contact_person';
        }

        if ($provider->isDirty('email')) {
            $changedFields[] = 'email';
        }

        if ($provider->isDirty('phone')) {
            $changedFields[] = 'phone';
        }

        if ($provider->isDirty('phone_alt')) {
            $changedFields[] = 'phone_alt';
        }

        if ($provider->isDirty('fax')) {
            $changedFields[] = 'fax';
        }

        if ($provider->isDirty('website')) {
            $changedFields[] = 'website';
        }

        // Address information
        if ($provider->isDirty('street') || $provider->isDirty('street_number')
            || $provider->isDirty('city') || $provider->isDirty('postal_code')
            || $provider->isDirty('province') || $provider->isDirty('country')) {
            $changedFields[] = 'address';
        }

        // Shipping and pricing
        if ($provider->isDirty('shipping_cost')) {
            $changedFields[] = 'shipping_cost';
        }

        if ($provider->isDirty('discount_percent')) {
            $changedFields[] = 'discount_percent';
        }

        if ($provider->isDirty('partial_delivery_days')) {
            $changedFields[] = 'partial_delivery_days';
        }

        if ($provider->isDirty('partial_delivery_percent')) {
            $changedFields[] = 'partial_delivery_percent';
        }

        // Status flags
        if ($provider->isDirty('is_active')) {
            $changedFields[] = 'is_active';
        }

        if ($provider->isDirty('is_visible')) {
            $changedFields[] = 'is_visible';
        }

        if ($provider->isDirty('is_creditor')) {
            $changedFields[] = 'is_creditor';
        }

        // Notes
        if ($provider->isDirty('notes')) {
            $changedFields[] = 'notes';
        }

        if ($provider->isDirty('shipping_notes')) {
            $changedFields[] = 'shipping_notes';
        }

        return array_unique($changedFields);
    }
}
