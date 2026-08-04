<?php

namespace Modules\Supplier\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Jobs\SyncCategoriesJob;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Jobs\SyncPricesJob;
use Modules\Supplier\Jobs\SyncProductsJob;
use Modules\Supplier\Jobs\SyncProvidersFromErpJob;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * Orchestrator agent that coordinates multiple sync agents
 *
 * Spawns and manages concurrent sync operations, monitors progress,
 * handles failures and retries, and aggregates results.
 *
 * Supports:
 * - Syncing by type (product, category, price, provider, model)
 * - Supplier-specific syncs
 * - Parallel processing of multiple agents
 * - Progress monitoring and real-time updates
 * - Batch failure and retry handling
 */
class SyncCoordinatorAgent
{
    private array $activeAgents = [];

    private array $agentResults = [];

    private SyncBatch $coordinatorBatch;

    public function __construct(private SyncStatusService $syncStatusService) {}

    /**
     * Coordinate a synchronization operation by type
     *
     * Creates a batch and spawns appropriate sync agents based on sync type.
     *
     * @param  string  $syncType  Type: 'product', 'category', 'price', 'provider', 'model', 'all'
     * @param  int|null  $supplierId  Optional supplier ID for supplier-specific sync
     * @param  string  $triggeredBy  Trigger source: 'manual', 'scheduled', 'webhook', 'api'
     * @param  array|null  $filterCriteria  Optional filter criteria
     * @return array{
     *     success: bool,
     *     message: string,
     *     batch_id: int|null,
     *     sync_types: array,
     *     agents_started: int,
     *     total_items: int|null
     * }
     */
    public function coordinateSync(
        string $syncType,
        ?int $supplierId = null,
        string $triggeredBy = 'manual',
        ?array $filterCriteria = null
    ): array {
        try {
            Log::info('Coordinator sync started', [
                'sync_type' => $syncType,
                'supplier_id' => $supplierId,
                'triggered_by' => $triggeredBy,
            ]);

            // Create coordinator batch
            $this->coordinatorBatch = $this->createCoordinatorBatch(
                syncType: $syncType,
                supplierId: $supplierId,
                triggeredBy: $triggeredBy,
                filterCriteria: $filterCriteria
            );

            // Determine which agents to spawn
            $syncTypes = $this->determineSyncTypes($syncType);

            // Spawn agents in parallel
            $totalItems = $this->runAgentsInParallel($syncTypes, $supplierId, $filterCriteria);

            // Monitor progress
            $this->monitorProgress();

            // Handle batch completion
            return $this->handleBatchCompletion(
                batchId: $this->coordinatorBatch->id,
                syncTypes: $syncTypes,
                agentsStarted: count($this->activeAgents),
                totalItems: $totalItems
            );
        } catch (Exception $e) {
            Log::error('Coordinator sync failed', [
                'error' => $e->getMessage(),
                'batch_id' => $this->coordinatorBatch->id ?? null,
            ]);

            if (isset($this->coordinatorBatch)) {
                $this->coordinatorBatch->markAsFailed();
            }

            return [
                'success' => false,
                'message' => 'Coordinator sync failed: '.$e->getMessage(),
                'batch_id' => $this->coordinatorBatch->id ?? null,
                'sync_types' => [],
                'agents_started' => 0,
                'total_items' => 0,
            ];
        }
    }

    /**
     * Create a coordinator batch record
     */
    private function createCoordinatorBatch(
        string $syncType,
        ?int $supplierId,
        string $triggeredBy,
        ?array $filterCriteria
    ): SyncBatch {
        return SyncBatch::create([
            'supplier_id' => $supplierId,
            'batch_name' => "Coordinator: {$syncType} sync",
            'sync_type' => $syncType,
            'status' => 'pending',
            'priority' => 'normal',
            'batch_size' => 100,
            'total_batches' => 0,
            'processed_batches' => 0,
            'total_items' => 0,
            'processed_items' => 0,
            'failed_items' => 0,
            'retry_attempt' => 0,
            'max_retries' => 3,
            'triggered_by' => $triggeredBy,
            'filter_criteria' => $filterCriteria,
        ]);
    }

    /**
     * Determine which sync types to execute
     *
     * Translates 'all' to multiple sync types or returns single type.
     */
    private const SUPPORTED_SYNC_TYPES = ['product', 'category', 'price', 'provider', 'model'];

    /**
     * Sync types included when the caller requests 'all'. Excludes 'model',
     * which is a heavier ERP-driven import run on demand rather than as part
     * of a full sweep.
     */
    private const ALL_SYNC_TYPES = ['product', 'category', 'price', 'provider'];

    private function determineSyncTypes(string $syncType): array
    {
        $types = match ($syncType) {
            'all' => self::ALL_SYNC_TYPES,
            'products' => ['product'],
            'categories' => ['category'],
            'prices' => ['price'],
            'providers' => ['provider'],
            'models' => ['model'],
            default => [$syncType],
        };

        $invalid = array_diff($types, self::SUPPORTED_SYNC_TYPES);

        if ($invalid !== []) {
            throw new Exception('Unknown sync type: '.implode(', ', $invalid));
        }

        return $types;
    }

    /**
     * Run sync agents in parallel
     *
     * Spawns sync agents as queue jobs for parallel processing.
     *
     * @return int Total items across all agents
     */
    private function runAgentsInParallel(
        array $syncTypes,
        ?int $supplierId,
        ?array $filterCriteria
    ): int {
        $totalItems = 0;

        foreach ($syncTypes as $syncType) {
            try {
                // Create batch for this sync type
                $batch = SyncBatch::create([
                    'supplier_id' => $supplierId,
                    'batch_name' => "Sync: {$syncType}",
                    'sync_type' => $syncType,
                    'status' => 'pending',
                    'priority' => 'normal',
                    'batch_size' => 100,
                    'total_batches' => 0,
                    'processed_batches' => 0,
                    'total_items' => 0,
                    'processed_items' => 0,
                    'failed_items' => 0,
                    'retry_attempt' => 0,
                    'max_retries' => 3,
                    'triggered_by' => 'coordinator',
                    'filter_criteria' => $filterCriteria,
                ]);

                // Dispatch sync job. 'model' recibe los criterios de filtro completos
                // (date_from/date_field/web_filter/etc) — antes se ignoraban por
                // completo acá, así que cualquier sync manual traía TODO el histórico
                // sin importar lo configurado en el panel (causó una sincronización de
                // ~1500 items en vez de solo los pendientes recientes).
                $jobClass = $this->getSyncJobClass($syncType);
                if ($syncType === 'model') {
                    $criteria = $filterCriteria ?? [];
                    $jobClass::dispatch(
                        $batch,
                        $supplierId,
                        $criteria['limit'] ?? null,
                        (bool) ($criteria['force'] ?? false),
                        $criteria['mode'] ?? 'filter',
                        $criteria['date_from'] ?? null,
                        (bool) ($criteria['skip_ai'] ?? false),
                        (bool) ($criteria['dry_run'] ?? false),
                        $criteria['erp_model_id'] ?? null,
                        (bool) ($criteria['description_empty'] ?? false),
                        $criteria['web_filter'] ?? '2',
                        (bool) ($criteria['register_only'] ?? false),
                        $criteria['date_field'] ?? 'creation',
                    )->onQueue('sync');
                } else {
                    $jobClass::dispatch($batch, $supplierId)->onQueue('sync');
                }

                $this->activeAgents[$syncType] = [
                    'batch_id' => $batch->id,
                    'status' => 'dispatched',
                    'created_at' => now(),
                ];

                Log::info('Sync agent dispatched', [
                    'sync_type' => $syncType,
                    'batch_id' => $batch->id,
                    'supplier_id' => $supplierId,
                ]);

                // Count items to estimate total (approximate)
                $totalItems += $this->estimateTotalItems($syncType, $supplierId, $filterCriteria);
            } catch (Exception $e) {
                Log::error('Failed to dispatch sync agent', [
                    'sync_type' => $syncType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totalItems;
    }

    /**
     * Get the job class for a specific sync type
     *
     * Maps sync type to corresponding job class.
     * These jobs should be created as concrete implementations.
     */
    private function getSyncJobClass(string $syncType): string
    {
        return match ($syncType) {
            'product' => SyncProductsJob::class,
            'category' => SyncCategoriesJob::class,
            'price' => SyncPricesJob::class,
            'provider' => SyncProvidersFromErpJob::class,
            'model' => SyncModelsJob::class,
            default => throw new Exception("Unknown sync type: {$syncType}"),
        };
    }

    /**
     * Estimate total items to be synced
     *
     * Provides rough estimate for progress calculation.
     */
    private function estimateTotalItems(
        string $syncType,
        ?int $supplierId,
        ?array $filterCriteria
    ): int {
        try {
            return match ($syncType) {
                'product' => Product::query()
                    ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
                    ->when($filterCriteria, fn ($q) => $this->applyFilters($q, $filterCriteria))
                    ->count(),
                'category' => Category::query()
                    ->when($filterCriteria, fn ($q) => $this->applyFilters($q, $filterCriteria))
                    ->count(),
                'price' => Product::query()
                    ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
                    ->when($filterCriteria, fn ($q) => $this->applyFilters($q, $filterCriteria))
                    ->count(),
                'provider' => Supplier::query()
                    ->when($supplierId, fn ($q) => $q->where('id', $supplierId))
                    ->when($filterCriteria, fn ($q) => $this->applyFilters($q, $filterCriteria))
                    ->count(),
                default => 0,
            };
        } catch (Exception $e) {
            Log::warning('Failed to estimate items', [
                'sync_type' => $syncType,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Apply filter criteria to query.
     */
    private function applyFilters(Builder $query, array $filterCriteria): Builder
    {
        if (isset($filterCriteria['category_id'])) {
            $query->where('category_id', $filterCriteria['category_id']);
        }

        if (isset($filterCriteria['updated_since'])) {
            $query->where('updated_at', '>=', $filterCriteria['updated_since']);
        }

        if (isset($filterCriteria['status'])) {
            $query->where('status', $filterCriteria['status']);
        }

        return $query;
    }

    /**
     * Monitor progress of running agents.
     *
     * Each running batch persists progress data via SyncStatusService into
     * Redis (real-time) and the database (durable). This method aggregates
     * the current snapshot for every dispatched agent and refreshes the
     * coordinator batch counters so the UI sees a consistent view.
     */
    private function monitorProgress(): void
    {
        $totalProcessed = 0;
        $totalFailed = 0;
        $totalItems = 0;

        foreach ($this->activeAgents as $syncType => &$agentInfo) {
            try {
                $batchId = $agentInfo['batch_id'];
                $batch = SyncBatch::find($batchId);

                if (! $batch) {
                    continue;
                }

                $progress = $this->syncStatusService->getProgress($batchId);

                $agentInfo['progress'] = $progress;
                $agentInfo['status'] = $batch->status;
                $agentInfo['last_checked_at'] = now()->toIso8601String();

                $totalProcessed += (int) ($batch->processed_items ?? 0);
                $totalFailed += (int) ($batch->failed_items ?? 0);
                $totalItems += (int) ($batch->total_items ?? 0);
            } catch (Exception $e) {
                Log::warning('Failed to monitor sync agent', [
                    'sync_type' => $syncType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        unset($agentInfo);

        if (isset($this->coordinatorBatch) && $totalItems > 0) {
            $this->coordinatorBatch->update([
                'processed_items' => $totalProcessed,
                'failed_items' => $totalFailed,
            ]);
        }
    }

    /**
     * Handle batch completion
     *
     * Aggregates results from all agents and returns summary.
     */
    private function handleBatchCompletion(
        int $batchId,
        array $syncTypes,
        int $agentsStarted,
        int $totalItems
    ): array {
        // The coordinator batch's work is done: it dispatched the sub-jobs.
        // Sub-batches track actual progress; marking this one completed prevents
        // it from accumulating in the "running forever" bucket of the dashboard.
        $this->coordinatorBatch->update(['total_items' => $totalItems]);
        $this->coordinatorBatch->markAsCompleted();

        Log::info('Coordinator batch completed (sub-jobs dispatched)', [
            'batch_id' => $batchId,
            'sync_types' => $syncTypes,
            'agents_started' => $agentsStarted,
            'total_items' => $totalItems,
        ]);

        return [
            'success' => true,
            'message' => "Sync coordinator started {$agentsStarted} agent(s)",
            'batch_id' => $batchId,
            'sync_types' => $syncTypes,
            'agents_started' => $agentsStarted,
            'total_items' => $totalItems,
        ];
    }

    /**
     * Get the coordinator batch
     */
    public function getCoordinatorBatch(): SyncBatch
    {
        return $this->coordinatorBatch;
    }

    /**
     * Get all active agents
     */
    public function getActiveAgents(): array
    {
        return $this->activeAgents;
    }

    /**
     * Get aggregated results from all agents
     */
    public function getAgentResults(): array
    {
        return $this->agentResults;
    }

    /**
     * Record agent completion result
     *
     * Called by agents when they complete to report results back.
     */
    public function recordAgentResult(string $syncType, array $result): void
    {
        $this->agentResults[$syncType] = $result;
        $this->activeAgents[$syncType]['status'] = 'completed';

        Log::info('Agent result recorded', [
            'sync_type' => $syncType,
            'result' => $result,
        ]);

        // Check if all agents are complete
        if ($this->allAgentsComplete()) {
            $this->finalizeCoordinatorBatch();
        }
    }

    /**
     * Check if all agents have completed
     */
    private function allAgentsComplete(): bool
    {
        $activeCount = count($this->activeAgents);
        $completedCount = count(array_filter(
            $this->activeAgents,
            fn ($agent) => $agent['status'] === 'completed'
        ));

        return $activeCount > 0 && $activeCount === $completedCount;
    }

    /**
     * Finalize coordinator batch after all agents complete
     *
     * Aggregates metrics and marks coordinator batch as complete.
     */
    private function finalizeCoordinatorBatch(): void
    {
        if (! isset($this->coordinatorBatch)) {
            return;
        }

        try {
            $totalProcessed = 0;
            $totalFailed = 0;
            $totalSkipped = 0;

            foreach ($this->agentResults as $result) {
                $totalProcessed += $result['items_processed'] ?? 0;
                $totalFailed += $result['items_failed'] ?? 0;
                $totalSkipped += $result['items_skipped'] ?? 0;
            }

            $this->coordinatorBatch->update([
                'processed_items' => $totalProcessed,
                'failed_items' => $totalFailed,
                'status' => 'completed',
            ]);

            $this->coordinatorBatch->markAsCompleted();

            Log::info('Coordinator batch finalized', [
                'batch_id' => $this->coordinatorBatch->id,
                'total_processed' => $totalProcessed,
                'total_failed' => $totalFailed,
                'total_skipped' => $totalSkipped,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to finalize coordinator batch', [
                'batch_id' => $this->coordinatorBatch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel all running agents
     *
     * Requests cancellation of all active sync operations.
     */
    public function cancelAllAgents(): void
    {
        foreach ($this->activeAgents as $syncType => $agentInfo) {
            try {
                $this->syncStatusService->requestCancellation($agentInfo['batch_id']);

                Log::info('Cancellation requested for agent', [
                    'sync_type' => $syncType,
                    'batch_id' => $agentInfo['batch_id'],
                ]);
            } catch (Exception $e) {
                Log::error('Failed to request cancellation', [
                    'sync_type' => $syncType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark coordinator batch as cancelled
        $this->coordinatorBatch->markAsCancelled();
    }

    /**
     * Get progress summary for all agents
     *
     * Aggregates progress from all active agents.
     */
    public function getProgressSummary(): array
    {
        $summary = [
            'coordinator_batch_id' => $this->coordinatorBatch->id,
            'total_agents' => count($this->activeAgents),
            'agents' => [],
        ];

        foreach ($this->activeAgents as $syncType => $agentInfo) {
            try {
                $progress = $this->syncStatusService->getProgress($agentInfo['batch_id']);
                $summary['agents'][$syncType] = $progress;
            } catch (Exception $e) {
                Log::warning('Failed to get agent progress', [
                    'sync_type' => $syncType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }
}
