<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Supplier\Http\Requests\Sync\SaveSyncSettingsRequest;
use Modules\Supplier\Http\Requests\Sync\StartSyncRequest;
use Modules\Supplier\Http\Requests\Sync\SyncProviderProductsRequest;
use Modules\Supplier\Jobs\SyncCategoriesFromErpJob;
use Modules\Supplier\Jobs\SyncCharacteristicsFromErpJob;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Jobs\SyncProviderProductsJob;
use Modules\Supplier\Jobs\SyncProvidersFromErpJob;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Sync\ExcludedErpGroup;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Models\Sync\SyncLog;
use Modules\Supplier\Models\Sync\SyncSchedule;
use Modules\Supplier\Models\Sync\SyncSetting;
use Modules\Supplier\Models\Sync\SyncStatus;
use Modules\Supplier\Services\ContentGenerationService;
use Modules\Supplier\Services\SyncCoordinatorAgent;
use Modules\Supplier\Services\SyncStatusService;

class SupplierSyncController extends Controller
{
    public function __construct(
        protected SyncCoordinatorAgent $syncCoordinator,
        protected SyncStatusService $syncStatusService
    ) {}

    /**
     * Display the sync history/monitoring dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('can_sync_suppliers');

        $pageTitle = 'Sincronización';

        $batches = $this->buildBatchHistoryQuery($request);
        $stats = $this->buildBatchStats();

        $schedules = SyncSchedule::query()
            ->orderBy('sync_type')
            ->orderBy('hour')
            ->orderBy('minute')
            ->get();

        $scheduleStats = [
            'total' => $schedules->count(),
            'enabled' => $schedules->where('is_enabled', true)->count(),
            'disabled' => $schedules->where('is_enabled', false)->count(),
            'errors' => $schedules->where('last_run_status', 'error')->count(),
        ];

        $settings = $this->loadSyncSettings();
        $routes = $this->dashboardRoutes();

        return view('supplier::settings.views.sync.index', compact(
            'batches', 'stats', 'schedules', 'scheduleStats', 'settings', 'routes', 'pageTitle'
        ));
    }

    /**
     * Build the paginated batch history query honouring the request filters.
     */
    private function buildBatchHistoryQuery(Request $request): LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200], true) ? $perPage : 15;

        return SyncBatch::query()
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->get('sync_type'), fn ($q, $type) => $q->where('sync_type', $type))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{total: int, running: int, completed: int, failed: int, last_at: mixed, stale_pending: int}
     */
    private function buildBatchStats(): array
    {
        $aggregated = SyncBatch::query()->selectRaw(
            'COUNT(*) as total, '
            ."SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running, "
            ."SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, "
            ."SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed, "
            .'MAX(created_at) as last_at'
        )->first();

        // Batches that have been pending for more than 5 min likely indicate a downed worker.
        $stalePending = SyncBatch::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->count();

        return [
            'total' => (int) $aggregated->total,
            'running' => (int) $aggregated->running,
            'completed' => (int) $aggregated->completed,
            'failed' => (int) $aggregated->failed,
            'last_at' => $aggregated->last_at,
            'stale_pending' => $stalePending,
        ];
    }

    /**
     * Cache the sync settings map so the dashboard does not run six SELECTs
     * per load for values that change rarely. Invalidated on save by
     * {@see saveSettings()}.
     *
     * @return array<string, mixed>
     */
    private function loadSyncSettings(): array
    {
        $build = function (): array {
            $defaults = [
                'default_batch_size' => 50,
                'erp_timeout' => 60,
                'max_retries' => 3,
                'erp_base_url' => Setting::get('erp_api_url', env('ERP_URL', '')),
                'sync_enabled' => true,
                'log_level' => 'info',
                'filter_date_from' => config('supplier.erp_sync.filter_date_from', ''),
                'default_limit' => 0,
                // Opciones de ejecución por defecto
                'default_mode' => 'filter',
                'default_force' => '0',
                'default_description_empty' => '0',
                'default_web_filter' => '2',
                'default_skip_ai' => false,
                'default_dry_run' => false,
                'default_register_only' => false,
            ];

            $stored = SyncSetting::query()
                ->whereIn('key', array_keys($defaults))
                ->pluck('value', 'key')
                ->toArray();

            return array_replace($defaults, $stored);
        };

        return $build();
    }

    /**
     * @return array<string, string>
     */
    private function dashboardRoutes(): array
    {
        return [
            'sync_providers' => route('settings.suppliers.sync.sync-providers'),
            'sync_products' => route('settings.suppliers.sync.sync-products'),
            'sync_models' => route('settings.suppliers.sync.sync-models'),
            'progress' => route('settings.suppliers.sync.progress', ':batchId'),
            'retry' => route('settings.suppliers.sync.retry', ':batchId'),
            'cancel' => route('settings.suppliers.sync.cancel', ':batchId'),
            'bulk_retry' => route('settings.suppliers.sync.bulk-retry'),
            'bulk_cancel' => route('settings.suppliers.sync.bulk-cancel'),
            'bulk_delete' => route('settings.suppliers.sync.bulk-delete'),
            'config_store' => route('settings.suppliers.sync.config.store'),
            'config_update' => route('settings.suppliers.sync.config.update', ':uid'),
            'config_destroy' => route('settings.suppliers.sync.config.destroy', ':uid'),
            'config_toggle' => route('settings.suppliers.sync.config.toggle', ':uid'),
            'config_trigger' => route('settings.suppliers.sync.config.trigger', ':uid'),
            'config_data' => route('settings.suppliers.sync.config.data'),
            'config_status' => route('settings.suppliers.sync.config.status'),
            'config_reset' => route('settings.suppliers.sync.config.reset-status', ':uid'),
            'config_bulk_toggle' => route('settings.suppliers.sync.config.bulk-toggle'),
            'config_bulk_delete' => route('settings.suppliers.sync.config.bulk-delete'),
            'settings_save' => route('settings.suppliers.sync.settings.save'),
            'test_run' => route('settings.suppliers.sync.test.run'),
            'erp_model_count' => route('settings.suppliers.sync.erp-model-count'),
        ];
    }

    /**
     * Show details of a specific sync batch
     */
    public function show(Request $request, int $batchId): View
    {
        $this->authorize('can_sync_suppliers');

        $batch = SyncBatch::findOrFail($batchId);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200], true) ? $perPage : 20;

        // ── Logs ──────────────────────────────────────────────────────────────
        $logsSearch = $request->get('logs_search');
        $logsResult = $request->get('logs_result');

        $logsQuery = $batch->logs()->orderByDesc('created_at');

        if ($logsSearch) {
            // La "Referencia" (código del producto) no vive en supplier_sync_logs —
            // se resuelve la lista de entity_id/erp_id de productos que matchean
            // primero, para poder buscarla igual que cualquier otro campo.
            [$matchingEntityIds, $matchingErpIds] = $this->matchProductsByCode($logsSearch);

            $logsQuery->where(function ($q) use ($logsSearch, $matchingEntityIds, $matchingErpIds) {
                $q->where('message', 'LIKE', "%{$logsSearch}%")
                    ->orWhere('error_message', 'LIKE', "%{$logsSearch}%")
                    ->orWhere('entity_type', 'LIKE', "%{$logsSearch}%")
                    ->orWhere('action', 'LIKE', "%{$logsSearch}%")
                    ->orWhere('erp_id', $logsSearch)
                    ->orWhereIn('entity_id', $matchingEntityIds)
                    ->orWhereIn('erp_id', $matchingErpIds);
            });
        }

        if ($logsResult) {
            $logsQuery->where('result', $logsResult);
        }

        $logs = $logsQuery->paginate($perPage, ['*'], 'logs_page')->withQueryString();
        $this->attachProductReferences($logs->getCollection());

        // ── Failures ──────────────────────────────────────────────────────────
        $failuresSearch = $request->get('failures_search');
        $failuresStatus = $request->get('failures_status');
        $failuresType = $request->get('failures_type');

        $failuresQuery = $batch->failures()->orderByDesc('created_at');

        if ($failuresSearch) {
            [, $matchingErpIdsForFailures] = $this->matchProductsByCode($failuresSearch);

            $failuresQuery->where(function ($q) use ($failuresSearch, $matchingErpIdsForFailures) {
                $q->where('error_message', 'LIKE', "%{$failuresSearch}%")
                    ->orWhere('erp_id', $failuresSearch)
                    ->orWhereIn('erp_id', $matchingErpIdsForFailures)
                    // Referencia "fallback": el código que el ERP mandó pero que nunca
                    // llegó a crear un producto real (ej. fallos "sin proveedor").
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(changed_data, '$.code')) LIKE ?", ["%{$failuresSearch}%"]);
            });
        }

        if ($failuresStatus) {
            $failuresQuery->where('failure_status', $failuresStatus);
        }

        if ($failuresType) {
            $failuresQuery->where('failure_type', $failuresType);
        }

        $failures = $failuresQuery->paginate($perPage, ['*'], 'failures_page')->withQueryString();
        $this->attachFailureReferences($failures->getCollection());

        // Tab badge counters use the unfiltered totals.
        $logsCount = $batch->logs()->count();
        $failuresCount = $batch->failures()->count();
        $tab = $request->get('tab', 'logs');

        $pageTitle = 'Detalles de sincronización';
        $breadcrumb = 'Configuración / Proveedores / Sincronización / Detalles';

        return view('supplier::settings.views.sync.show', compact(
            'batch',
            'logs',
            'failures',
            'logsCount',
            'failuresCount',
            'tab',
            'logsSearch',
            'logsResult',
            'failuresSearch',
            'failuresStatus',
            'failuresType',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Bulk delete log entries belonging to a batch.
     */
    public function bulkDestroyLogs(Request $request, int $batchId): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $batch = SyncBatch::findOrFail($batchId);

        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = $batch->logs()->whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} log(s) eliminado(s).",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Return JSON detail for a single sync log entry.
     */
    public function showLog(int $batchId, int $logId): JsonResponse
    {
        $log = SyncLog::where('batch_id', $batchId)->findOrFail($logId);
        $this->attachProductReferences(collect([$log]));

        return response()->json(['success' => true, 'log' => $log]);
    }

    /**
     * Busca productos cuyo código coincida con el término de búsqueda y
     * devuelve [entityIds, erpIds] para poder incluirlos en un WHERE IN,
     * ya que "Referencia" no es una columna de supplier_sync_logs/_failures.
     *
     * @return array{0: Collection<int, int>, 1: Collection<int, int>}
     */
    private function matchProductsByCode(string $search): array
    {
        $products = Product::where('code', 'LIKE', "%{$search}%")->get(['id', 'erp_id']);

        return [$products->pluck('id'), $products->pluck('erp_id')->filter()];
    }

    /**
     * Adjunta ->reference (código del producto) a cada SyncLog de la colección.
     *
     * El log en sí no guarda el código/referencia del producto (solo entity_id
     * y erp_id) — se resuelve en lote contra supplier_products para que la
     * tabla/modal de detalle lo muestren sin N+1 queries ni que el usuario
     * tenga que ir a buscar el producto por su cuenta.
     */
    private function attachProductReferences(Collection $logs): void
    {
        $relevant = $logs->filter(fn (SyncLog $log) => in_array($log->entity_type, ['model', 'product'], true));

        if ($relevant->isEmpty()) {
            return;
        }

        $entityIds = $relevant->pluck('entity_id')->filter()->unique()->values();
        $erpIdsWithoutEntity = $relevant->whereNull('entity_id')->pluck('erp_id')->filter()->unique()->values();

        $productsByEntityId = $entityIds->isNotEmpty()
            ? Product::whereIn('id', $entityIds)->get(['id', 'erp_id', 'code'])->keyBy('id')
            : collect();

        $productsByErpId = $erpIdsWithoutEntity->isNotEmpty()
            ? Product::whereIn('erp_id', $erpIdsWithoutEntity)->get(['id', 'erp_id', 'code'])->keyBy('erp_id')
            : collect();

        foreach ($logs as $log) {
            $product = $log->entity_id
                ? ($productsByEntityId[$log->entity_id] ?? null)
                : ($log->erp_id ? ($productsByErpId[$log->erp_id] ?? null) : null);

            $log->reference = $product?->code;
            $log->product_id = $product?->id;
        }
    }

    /**
     * Adjunta ->reference (código del producto) a cada SyncFailure de la
     * colección: por erp_id contra supplier_products si el producto ya
     * existe, o desde cambios_data enviados al ERP si nunca llegó a crearse
     * (p. ej. fallos "sin proveedor" — el dato existe igual).
     */
    private function attachFailureReferences(Collection $failures): void
    {
        $erpIds = $failures->pluck('erp_id')->filter()->unique()->values();

        $productsByErpId = $erpIds->isNotEmpty()
            ? Product::whereIn('erp_id', $erpIds)->pluck('code', 'erp_id')
            : collect();

        foreach ($failures as $failure) {
            $fromProduct = $failure->erp_id ? ($productsByErpId[$failure->erp_id] ?? null) : null;
            $fromErpData = $failure->changed_data['code'] ?? $failure->changed_data['codigo'] ?? null;

            $failure->reference = $fromProduct ?? $fromErpData;
        }
    }

    /**
     * POST endpoint to start a new sync
     */
    public function startSync(StartSyncRequest $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        try {
            $result = $this->syncCoordinator->coordinateSync(
                syncType: $request->validated('sync_type'),
                supplierId: $request->validated('supplier_id'),
                triggeredBy: $request->validated('triggered_by'),
                filterCriteria: $request->validated('filter_criteria')
            );

            if (! $result['success']) {
                Log::warning('Sync coordination failed', [
                    'sync_type' => $request->input('sync_type'),
                    'supplier_id' => $request->input('supplier_id'),
                    'error' => $result['message'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo iniciar la sincronización: '.$result['message'],
                ], 422);
            }

            Log::info('Sync coordination started successfully', [
                'batch_id' => $result['batch_id'],
                'sync_type' => $request->input('sync_type'),
                'supplier_id' => $request->input('supplier_id'),
                'agents_started' => $result['agents_started'],
                'total_items' => $result['total_items'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sincronización iniciada correctamente.',
                'batch_id' => $result['batch_id'],
                'sync_types' => $result['sync_types'],
                'agents_started' => $result['agents_started'],
                'total_items' => $result['total_items'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting sync', [
                'sync_type' => $request->input('sync_type'),
                'supplier_id' => $request->input('supplier_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización',
            ], 500);
        }
    }

    /**
     * POST endpoint to cancel a running sync
     */
    public function cancelSync(int $batchId): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $result = $this->performCancel($batchId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * POST endpoint to retry a failed sync
     */
    public function retrySync(int $batchId): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $result = $this->performRetry($batchId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Bulk retry the selected batches.
     */
    public function bulkRetry(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $started = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->validatedBatchIds($request) as $id) {
            $result = $this->performRetry($id);

            if ($result['success']) {
                $started++;

                continue;
            }

            $skipped++;
            $errors[] = "Ejecución #{$id}: {$result['message']}";
        }

        return response()->json([
            'success' => true,
            'message' => "Reintentos iniciados: {$started}. Omitidos: {$skipped}.",
            'errors' => $errors,
        ]);
    }

    /**
     * Bulk cancel the selected batches that are currently running.
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $cancelled = 0;
        $skipped = 0;
        $errors = [];

        foreach ($this->validatedBatchIds($request) as $id) {
            $result = $this->performCancel($id);

            if ($result['success']) {
                $cancelled++;

                continue;
            }

            $skipped++;
            $errors[] = "Ejecución #{$id}: {$result['message']}";
        }

        return response()->json([
            'success' => true,
            'message' => "Canceladas: {$cancelled}. Omitidas: {$skipped}.",
            'errors' => $errors,
        ]);
    }

    /**
     * Cancel a single running batch. Shared by {@see cancelSync()} and
     * {@see bulkCancel()} so the work is not re-serialised through JSON.
     *
     * @return array{success: bool, message: string, status?: string}
     */
    private function performCancel(int|string $batchId): array
    {
        $batch = SyncBatch::find($batchId);

        if (! $batch) {
            return ['success' => false, 'message' => 'Ejecución no encontrada.'];
        }

        if (! $batch->canCancel()) {
            return ['success' => false, 'message' => 'Solo se pueden cancelar sincronizaciones pendientes o en progreso.'];
        }

        try {
            if (SyncStatus::where('batch_id', $batch->id)->exists()) {
                $this->syncStatusService->requestCancellation($batch->id);
            }

            $batch->markAsCancelled();

            Log::info('Sync cancelled', ['batch_id' => $batch->id, 'sync_type' => $batch->sync_type]);

            return ['success' => true, 'message' => 'Sincronización cancelada correctamente.', 'status' => $batch->status];
        } catch (\Exception $e) {
            Log::error('Error cancelling sync', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Error al cancelar sincronización'];
        }
    }

    /**
     * Retry a single failed/cancelled batch. Shared by {@see retrySync()} and
     * {@see bulkRetry()}.
     *
     * @return array{success: bool, message: string, batch_id?: int}
     */
    private function performRetry(int|string $batchId): array
    {
        $batch = SyncBatch::find($batchId);

        if (! $batch) {
            return ['success' => false, 'message' => 'Ejecución no encontrada.'];
        }

        if (! $batch->canRetry()) {
            return ['success' => false, 'message' => 'Solo se pueden reintentar sincronizaciones fallidas o canceladas que no hayan excedido el máximo de reintentos.'];
        }

        try {
            $batch->incrementRetryAttempt();

            $dispatch = match ($batch->sync_type) {
                'provider' => $this->dispatchProviderSync($batch),
                'category' => $this->dispatchCategorySync($batch),
                'product' => $this->dispatchProductSync($batch),
                'model' => $this->dispatchModelSync($batch),
                default => ['success' => false, 'message' => 'Tipo de sincronización no soportado para reintento.'],
            };

            if (! ($dispatch['success'] ?? false)) {
                Log::warning('Sync retry failed', [
                    'batch_id' => $batch->id,
                    'sync_type' => $batch->sync_type,
                    'error' => $dispatch['message'] ?? null,
                ]);

                return ['success' => false, 'message' => 'No se pudo reintentar la sincronización: '.($dispatch['message'] ?? '')];
            }

            Log::info('Sync retry started', [
                'original_batch_id' => $batch->id,
                'new_batch_id' => $dispatch['batch_id'],
                'sync_type' => $batch->sync_type,
                'retry_attempt' => $batch->retry_attempt,
            ]);

            return ['success' => true, 'message' => 'Reintento de sincronización iniciado correctamente.', 'batch_id' => $dispatch['batch_id']];
        } catch (\Exception $e) {
            Log::error('Error retrying sync', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'message' => 'Error al reintentar sincronización'];
        }
    }

    /**
     * Bulk delete the selected batches (running ones are kept).
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $ids = $this->validatedBatchIds($request);

        $running = SyncBatch::whereIn('id', $ids)->where('status', 'running')->count();
        $deleted = SyncBatch::whereIn('id', $ids)->where('status', '!=', 'running')->delete();

        $message = "{$deleted} ejecución(es) eliminada(s).";
        if ($running > 0) {
            $message .= " {$running} en progreso no se eliminaron.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'deleted' => $deleted,
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function validatedBatchIds(Request $request): array
    {
        return $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];
    }

    /**
     * Dispatch a sync retry for any sync type. Centralised helper that
     * replaces the previous dispatchProviderSync/CategorySync/ProductSync/ModelSync
     * private methods which only differed in sync_type, job class, and a
     * couple of optional fields.
     *
     * @param  array<string,mixed>  $extraBatchFields
     * @param  array<int,mixed>  $extraJobArgs  Extra positional args appended after $batch
     * @return array{success: bool, batch_id: int}
     */
    private function dispatchSyncRetry(
        SyncBatch $originalBatch,
        string $syncType,
        string $jobClass,
        array $extraBatchFields = [],
        array $extraJobArgs = [],
    ): array {
        $batch = SyncBatch::create(array_merge([
            'batch_name' => $originalBatch->batch_name.' (retry)',
            'sync_type' => $syncType,
            'status' => 'pending',
            'priority' => $originalBatch->priority,
            'triggered_by' => 'retry',
            'trigger_details' => 'Retry of batch #'.$originalBatch->id,
        ], $extraBatchFields));

        $jobClass::dispatch($batch, ...$extraJobArgs)->onQueue('sync');

        return ['success' => true, 'batch_id' => $batch->id];
    }

    private function dispatchProviderSync(SyncBatch $originalBatch): array
    {
        return $this->dispatchSyncRetry(
            $originalBatch,
            'provider',
            SyncProvidersFromErpJob::class,
        );
    }

    private function dispatchCategorySync(SyncBatch $originalBatch): array
    {
        return $this->dispatchSyncRetry(
            $originalBatch,
            'category',
            SyncCategoriesFromErpJob::class,
        );
    }

    private function dispatchProductSync(SyncBatch $originalBatch): array
    {
        $erpProviderId = $originalBatch->filter_criteria['erp_provider_id'] ?? null;

        return $this->dispatchSyncRetry(
            $originalBatch,
            'product',
            SyncProviderProductsJob::class,
            extraBatchFields: ['filter_criteria' => $originalBatch->filter_criteria],
            extraJobArgs: [$erpProviderId],
        );
    }

    private function dispatchModelSync(SyncBatch $originalBatch): array
    {
        $criteria = $originalBatch->filter_criteria ?? [];
        $erpProviderId = $criteria['erp_provider_id'] ?? null;

        // Preserve all original parameters so the retry is an exact replay.
        $mode = $criteria['mode'] ?? ($erpProviderId ? 'legacy' : 'filter');
        $limit = isset($criteria['limit']) ? (int) $criteria['limit'] : null;
        $force = (bool) ($criteria['force'] ?? false);
        $dateFrom = $criteria['date_from'] ?? null;

        return $this->dispatchSyncRetry(
            $originalBatch,
            'model',
            SyncModelsJob::class,
            extraBatchFields: ['filter_criteria' => $originalBatch->filter_criteria],
            extraJobArgs: [
                'erpProviderId' => $erpProviderId,
                'limit' => $limit,
                'force' => $force,
                'mode' => $mode,
                'dateFrom' => $dateFrom,
            ],
        );
    }

    /**
     * Sincronizar proveedores desde ERP Oracle
     */
    public function syncProvidersFromErp(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        return $this->dispatchErpSync(
            syncType: 'provider',
            batchName: 'Sync Providers from ERP',
            jobClass: SyncProvidersFromErpJob::class,
            triggerDetails: 'User-initiated sync from web UI',
            successMessage: 'Sincronización de proveedores iniciada correctamente.',
            errorLabel: 'proveedores',
        );
    }

    /**
     * Vista de panel de pruebas de sincronización
     */
    public function testIndex(): View
    {
        $this->authorize('can_sync_suppliers');

        $recentBatches = SyncBatch::query()
            ->where(function ($q) {
                $q->where('trigger_details', 'like', '%test%')
                    ->orWhere('batch_name', 'like', '%test%')
                    ->orWhere('batch_name', 'like', '%(limit%');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $routes = [
            'test_run' => route('settings.suppliers.sync.test.run'),
            'erp_model_count' => route('settings.suppliers.sync.erp-model-count'),
            'progress' => route('settings.suppliers.sync.progress', ':batchId'),
            'cancel' => route('settings.suppliers.sync.cancel', ':batchId'),
            'show' => route('settings.suppliers.sync.show', ':batchId'),
            'bulk_delete' => route('settings.suppliers.sync.bulk-delete'),
            'bulk_retry' => route('settings.suppliers.sync.bulk-retry'),
            'bulk_cancel' => route('settings.suppliers.sync.bulk-cancel'),
        ];

        $globalPrompt = Prompt::query()
            ->where('scope', 'global')
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        $excludedGroupsCount = ExcludedErpGroup::count();

        $settings = SyncSetting::query()->pluck('value', 'key')->toArray();

        return view('supplier::settings.views.sync.test', compact('recentBatches', 'routes', 'globalPrompt', 'excludedGroupsCount', 'settings'));
    }

    /**
     * Devuelve el total de modelos disponibles en el ERP con los filtros de sync activos.
     * Usado en el panel de pruebas para mostrar el máximo real al usuario.
     */
    public function erpModelCount(): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        try {
            $storedSettings = SyncSetting::query()->pluck('value', 'key')->toArray();

            $erpBaseUrl = rtrim(Setting::get('supplier.erp_internal_url', config('supplier.erp_internal_url', 'http://nginx')), '/').'/api/erp';

            $params = ['limit' => 1, 'offset' => 0, 'include_total' => 1];

            // Por defecto NO se filtra por descripción vacía: el conteo refleja todos los modelos
            // que el sync registrará (con y sin descripción ERP).
            $descEmpty = $storedSettings['default_description_empty'] ?? '0';
            if ($descEmpty !== '' && $descEmpty !== '0') {
                $params['description_empty'] = (int) $descEmpty;
            }

            $webFilter = $storedSettings['default_web_filter'] ?? '2';
            if ($webFilter !== '') {
                $params['web'] = (int) $webFilter;
            }

            $dateFrom = $storedSettings['filter_date_from'] ?? null;
            if ($dateFrom) {
                $params['date_from'] = $dateFrom;
                $params['date_field'] = 'creation';
            }

            $response = Http::timeout(15)->get("{$erpBaseUrl}/products/filter", $params);

            if (! $response->successful()) {
                return response()->json(['success' => false, 'total' => null]);
            }

            $total = $response->json('pagination.total');

            return response()->json(['success' => true, 'total' => $total, 'filters' => $params]);
        } catch (\Exception $e) {
            Log::warning('erpModelCount failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'total' => null]);
        }
    }

    /**
     * Ejecutar sincronización de prueba con parámetros configurables
     */
    public function testRun(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $validated = $request->validate([
            'sync_type' => 'required|in:model,provider,product,category',
            'limit' => 'nullable|integer|min:1|max:5000',
            'erp_provider_id' => 'nullable|integer|min:1',
            'erp_model_id' => 'nullable|integer|min:1',
            'force' => 'boolean',
            'mode' => 'nullable|in:filter,legacy',
            'date_from' => 'nullable|date_format:Y-m-d',
            'skip_ai' => 'boolean',
            'dry_run' => 'boolean',
            'description_empty' => 'nullable|boolean',
            'web_filter' => 'nullable|string|in:2',
            'register_only' => 'boolean',
            'date_field' => 'nullable|in:creation,modification',
        ]);

        $syncType = $validated['sync_type'];
        $limit = $validated['limit'] ?? null;
        $erpProviderId = $validated['erp_provider_id'] ?? null;
        $erpModelId = $validated['erp_model_id'] ?? null;
        $force = (bool) ($validated['force'] ?? false);
        $mode = $validated['mode'] ?? 'filter';
        $dateFrom = $validated['date_from'] ?? null;
        $skipAi = (bool) ($validated['skip_ai'] ?? false);
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        // El registro de productos no depende de la descripción ERP: se traen todos (con y sin descripción).
        $descriptionEmpty = isset($validated['description_empty']) ? (bool) $validated['description_empty'] : false;
        // Único estado sincronizable: pendientes de publicar en web (web=2).
        $webFilter = '2';
        $registerOnly = (bool) ($validated['register_only'] ?? false);
        $dateField = $validated['date_field'] ?? 'creation';

        $labels = ['model' => 'Modelos', 'provider' => 'Proveedores', 'product' => 'Productos', 'category' => 'Categorías'];
        $label = $labels[$syncType] ?? 'Sincronización';

        $nameParts = ["Test {$label}"];
        if ($erpModelId) {
            $nameParts[] = "modelo #{$erpModelId}";
        } elseif ($limit) {
            $nameParts[] = "limit {$limit}";
        }
        if ($erpProviderId) {
            $nameParts[] = "prov #{$erpProviderId}";
        }
        if ($dateFrom) {
            $nameParts[] = "desde {$dateFrom}";
        }
        if ($dryRun) {
            $nameParts[] = '[DRY-RUN]';
        }
        if ($skipAi) {
            $nameParts[] = '[sin IA]';
        }
        if ($registerOnly) {
            $nameParts[] = '[solo registrar]';
        }
        $name = implode(' ', $nameParts);

        try {
            $batch = SyncBatch::create([
                'batch_name' => $name,
                'sync_type' => $syncType,
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => $erpModelId ? 1 : ($limit ?? 100),
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => 'test-panel',
                'filter_criteria' => array_filter([
                    'limit' => $limit,
                    'erp_provider_id' => $erpProviderId,
                    'erp_model_id' => $erpModelId,
                    'force' => $force ?: null,
                    'mode' => $mode,
                    'date_from' => $dateFrom,
                    'skip_ai' => $skipAi ?: null,
                    'dry_run' => $dryRun ?: null,
                    'register_only' => $registerOnly ?: null,
                    'date_field' => $dateField !== 'creation' ? $dateField : null,
                ]),
            ]);

            match ($syncType) {
                'model' => SyncModelsJob::dispatch($batch, $erpProviderId, $erpModelId ? 1 : $limit, $force, $mode, $dateFrom, $skipAi, $dryRun, $erpModelId, $descriptionEmpty, $webFilter, $registerOnly, $dateField)->onQueue('sync'),
                'provider' => SyncProvidersFromErpJob::dispatch($batch)->onQueue('sync'),
                'product' => SyncProviderProductsJob::dispatch($batch, $erpProviderId, $limit)->onQueue('sync'),
                'category' => SyncCategoriesFromErpJob::dispatch($batch)->onQueue('sync'),
            };

            Log::info('Test sync dispatched', ['batch_id' => $batch->id, 'sync_type' => $syncType]);

            return response()->json([
                'success' => true,
                'message' => "Prueba de {$label} encolada. Sigue el progreso con el batch_id.",
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
            ]);
        } catch (\Exception $e) {
            Log::error('Test sync error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
                'batch_id' => isset($batch) ? $batch->id : null,
            ], 500);
        }
    }

    /**
     * Sincronizar modelos desde ERP Oracle
     */
    public function syncModelsFromErp(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $limit = $request->input('limit') ? (int) $request->input('limit') : null;
        $dateFrom = $request->input('date_from') ?: null;

        $batchName = 'Sync Models from ERP';
        if ($limit) {
            $batchName .= " (limit {$limit})";
        }
        if ($dateFrom) {
            $batchName .= " desde {$dateFrom}";
        }

        $filterCriteria = array_filter(['limit' => $limit, 'date_from' => $dateFrom]);

        return $this->dispatchErpSync(
            syncType: 'model',
            batchName: $batchName,
            jobClass: SyncModelsJob::class,
            triggerDetails: 'User-initiated model sync from web UI',
            successMessage: 'Sincronización de modelos iniciada correctamente.',
            errorLabel: 'modelos',
            batchSize: $limit ?? 100,
            filterCriteria: $filterCriteria ?: null,
            jobArgs: [null, $limit, false, $dateFrom],
            logContext: ['limit' => $limit, 'date_from' => $dateFrom],
        );
    }

    /**
     * Sincronizar jerarquía de categorías desde ERP Oracle
     */
    public function syncCategoriesFromErp(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        return $this->dispatchErpSync(
            syncType: 'category',
            batchName: 'Sync Categories from ERP',
            jobClass: SyncCategoriesFromErpJob::class,
            triggerDetails: 'User-initiated category hierarchy sync from web UI',
            successMessage: 'Sincronización de categorías iniciada correctamente.',
            errorLabel: 'categorías',
        );
    }

    /**
     * Sincronizar características de productos desde ERP Oracle
     */
    public function syncCharacteristicsFromErp(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        return $this->dispatchErpSync(
            syncType: 'characteristic',
            batchName: 'Sync Characteristics from ERP',
            jobClass: SyncCharacteristicsFromErpJob::class,
            triggerDetails: 'User-initiated characteristics sync from web UI',
            successMessage: 'Sincronización de características iniciada correctamente.',
            errorLabel: 'características',
        );
    }

    /**
     * Create a SyncBatch, dispatch the given job on the `sync` queue and
     * return the standard JSON envelope. Shared by the ERP "sync now"
     * endpoints which only differ in batch name, sync type and job class.
     *
     * @param  class-string  $jobClass
     * @param  array<string, mixed>|null  $filterCriteria
     * @param  array<int, mixed>  $jobArgs  Extra positional args appended after $batch
     * @param  array<string, mixed>  $logContext
     */
    private function dispatchErpSync(
        string $syncType,
        string $batchName,
        string $jobClass,
        string $triggerDetails,
        string $successMessage,
        string $errorLabel,
        int $batchSize = 100,
        ?array $filterCriteria = null,
        array $jobArgs = [],
        array $logContext = [],
    ): JsonResponse {
        try {
            $batch = SyncBatch::create([
                'batch_name' => $batchName,
                'sync_type' => $syncType,
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => $batchSize,
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => $triggerDetails,
                'filter_criteria' => $filterCriteria,
            ]);

            $jobClass::dispatch($batch, ...$jobArgs)->onQueue('sync');

            Log::info("ERP {$syncType} sync job dispatched", ['batch_id' => $batch->id] + $logContext);

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'batch_id' => $batch->id,
                'batch_uid' => $batch->uid,
            ]);
        } catch (\Exception $e) {
            Log::error("Error starting ERP {$syncType} sync", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Error al iniciar sincronización de {$errorLabel}",
            ], 500);
        }
    }

    /**
     * Sincronizar productos de proveedores desde ERP Oracle
     */
    public function syncProviderProductsFromErp(SyncProviderProductsRequest $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        try {
            $erpProviderId = $request->validated('erp_provider_id');

            $batchName = $erpProviderId
                ? "Sync Products from Provider #{$erpProviderId}"
                : 'Sync Products from All Providers';

            // Crear batch de sincronización
            $batch = SyncBatch::create([
                'batch_name' => $batchName,
                'sync_type' => 'product',
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => 50,
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => 'User-initiated provider products sync from web UI',
                'filter_criteria' => $erpProviderId ? ['erp_provider_id' => $erpProviderId] : null,
            ]);

            // Despachar job
            SyncProviderProductsJob::dispatch($batch, $erpProviderId)->onQueue('sync');

            Log::info('ERP provider products sync job dispatched', [
                'batch_id' => $batch->id,
                'erp_provider_id' => $erpProviderId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sincronización de productos iniciada correctamente.',
                'batch_id' => $batch->id,
                'batch_uid' => $batch->uid,
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting ERP provider products sync', [
                'erp_provider_id' => $request->input('erp_provider_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización de productos',
            ], 500);
        }
    }

    /**
     * Guardar configuración global de sincronización
     */
    public function saveSettings(SaveSyncSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Cache::forget('supplier:sync:settings');

        foreach ($validated as $key => $value) {
            if ($value === null) {
                SyncSetting::where('key', $key)->delete();

                continue;
            }
            $type = match ($key) {
                'default_batch_size', 'erp_timeout', 'max_retries', 'default_limit' => 'int',
                'sync_enabled', 'default_skip_ai', 'default_dry_run', 'default_register_only' => 'bool',
                default => 'string',
            };
            SyncSetting::setValue($key, $value, $type);

            if ($key === 'erp_base_url') {
                Setting::set('erp_api_url', $value);
                Setting::clearErpSettingsCache();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente.',
        ]);
    }

    /**
     * Obtener progreso de sincronización en tiempo real
     */
    public function getSyncProgress(int $batchId): JsonResponse
    {
        try {
            $batch = SyncBatch::findOrFail($batchId);

            $recentLogs = SyncLog::where('batch_id', $batchId)
                ->orderBy('id', 'desc')
                ->limit(60)
                ->get(['id', 'erp_id', 'entity_id', 'action', 'result', 'message', 'error_message', 'created_at'])
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'erp_id' => $l->erp_id,
                    'action' => $l->action,
                    'result' => $l->result,
                    'msg' => $l->result === 'failed' ? ($l->error_message ?? $l->message) : $l->message,
                    'at' => $l->created_at?->format('H:i:s'),
                ])
                ->reverse()
                ->values();

            $isTerminal = in_array($batch->status, ['completed', 'failed', 'cancelled']);
            $aiCost = null;
            if ($isTerminal) {
                try {
                    $aiCost = (float) DB::table('supplier_ai_costs')
                        ->where('batch_id', (string) $batch->id)
                        ->selectRaw('SUM(COALESCE(input_cost,0) + COALESCE(output_cost,0) + COALESCE(web_search_cost,0)) as total')
                        ->value('total') ?? 0.0;
                } catch (\Throwable) {
                    $aiCost = null;
                }
            }

            return response()->json([
                'success' => true,
                'batch' => [
                    'id' => $batch->id,
                    'uid' => $batch->uid,
                    'batch_name' => $batch->batch_name,
                    'sync_type' => $batch->sync_type,
                    'status' => $batch->status,
                    'total_items' => $batch->total_items,
                    'processed_items' => $batch->processed_items,
                    'failed_items' => $batch->failed_items,
                    'progress_percentage' => $batch->progress_percentage,
                    'success_rate' => $batch->success_rate,
                    'started_at' => $batch->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $batch->completed_at?->format('Y-m-d H:i:s'),
                    'duration_seconds' => $batch->duration_seconds,
                    'ai_cost' => $aiCost,
                ],
                'recent_logs' => $recentLogs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener progreso',
            ], 500);
        }
    }

    /**
     * Vista de limpieza de datos de prueba.
     */
    public function cleanupIndex(): View
    {
        $this->authorize('can_sync_suppliers');

        $counts = $this->getCleanupCounts();

        $pageTitle = 'Limpiar datos de prueba';
        $breadcrumb = 'Sincronización / Limpiar datos';

        return view('supplier::settings.views.sync.cleanup', compact('counts', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Ejecutar limpieza de los grupos seleccionados.
     */
    public function cleanupRun(Request $request): JsonResponse
    {
        $this->authorize('can_sync_suppliers');

        $groups = $request->validate([
            'groups' => 'required|array|min:1',
            'groups.*' => 'in:content,products,suppliers,categories,history',
        ])['groups'];

        $deleted = [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($groups as $group) {
            switch ($group) {
                case 'content':
                    $deleted['content'] = [
                        'supplier_ai_audit_logs' => DB::table('supplier_ai_audit_logs')->count(),
                        'supplier_ai_costs' => DB::table('supplier_ai_costs')->count(),
                        'supplier_ai_content_versions' => DB::table('supplier_ai_content_versions')->count(),
                        'supplier_ai_content_statuses_data' => 0,
                        'supplier_content_validations' => DB::table('supplier_content_validations')->count(),
                        'supplier_content_logs' => DB::table('supplier_content_logs')->count(),
                        'supplier_ai_contents' => DB::table('supplier_ai_contents')->count(),
                    ];
                    foreach (array_keys($deleted['content']) as $t) {
                        if ($t !== 'supplier_ai_content_statuses_data') {
                            DB::table($t)->truncate();
                        }
                    }
                    Cache::forget(ContentGenerationService::STATS_CACHE_KEY);
                    break;

                case 'products':
                    $deleted['products'] = [
                        'supplier_product_chat_messages' => DB::table('supplier_product_chat_messages')->count(),
                        'supplier_product_chats' => DB::table('supplier_product_chats')->count(),
                        'supplier_product_translations' => DB::table('supplier_product_translations')->count(),
                        'supplier_product_prices' => DB::table('supplier_product_prices')->count(),
                        'supplier_product_attributes' => DB::table('supplier_product_attributes')->count(),
                        'supplier_products' => DB::table('supplier_products')->count(),
                    ];
                    foreach (array_keys($deleted['products']) as $t) {
                        DB::table($t)->truncate();
                    }
                    break;

                case 'suppliers':
                    $deleted['suppliers'] = [
                        'supplier_supplier_categories' => DB::table('supplier_supplier_categories')->count(),
                        'supplier_sources' => DB::table('supplier_sources')->count(),
                        'suppliers' => DB::table('suppliers')->count(),
                    ];
                    foreach (array_keys($deleted['suppliers']) as $t) {
                        DB::table($t)->truncate();
                    }
                    break;

                case 'categories':
                    $deleted['categories'] = [
                        'supplier_subfamilies' => DB::table('supplier_subfamilies')->count(),
                        'supplier_categories' => DB::table('supplier_categories')->count(),
                        'supplier_sports' => DB::table('supplier_sports')->count(),
                    ];
                    foreach (array_keys($deleted['categories']) as $t) {
                        DB::table($t)->truncate();
                    }
                    break;

                case 'history':
                    $deleted['history'] = [
                        'supplier_sync_logs' => DB::table('supplier_sync_logs')->count(),
                        'supplier_sync_failures' => DB::table('supplier_sync_failures')->count(),
                        'supplier_sync_statuses' => DB::table('supplier_sync_statuses')->count(),
                        'supplier_sync_batches' => DB::table('supplier_sync_batches')->count(),
                    ];
                    // Orden: primero logs/failures/statuses que referencian batches, luego batches
                    foreach (['supplier_sync_logs', 'supplier_sync_failures', 'supplier_sync_statuses', 'supplier_sync_audit', 'supplier_sync_batches'] as $t) {
                        DB::table($t)->truncate();
                    }
                    break;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $totalDeleted = collect($deleted)->flatten()->sum();

        Log::warning('Cleanup ejecutado', [
            'groups' => $groups,
            'deleted' => $deleted,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Limpieza completada. {$totalDeleted} registros eliminados.",
            'deleted' => $deleted,
            'counts' => $this->getCleanupCounts(),
        ]);
    }

    private function getCleanupCounts(): array
    {
        return [
            'content' => [
                'label' => 'Contenido generado (IA)',
                'description' => 'Contenidos AI, logs, versiones, validaciones y costos',
                'icon' => 'fas fa-robot',
                'tables' => [
                    'Contenidos AI' => DB::table('supplier_ai_contents')->count(),
                    'Logs contenido' => DB::table('supplier_content_logs')->count(),
                    'Versiones' => DB::table('supplier_ai_content_versions')->count(),
                    'Costos IA' => DB::table('supplier_ai_costs')->count(),
                ],
                'total' => DB::table('supplier_ai_contents')->count()
                         + DB::table('supplier_content_logs')->count()
                         + DB::table('supplier_ai_content_versions')->count()
                         + DB::table('supplier_ai_costs')->count(),
            ],
            'products' => [
                'label' => 'Productos',
                'description' => 'Productos, atributos, chats y traducciones',
                'icon' => 'fas fa-box',
                'tables' => [
                    'Productos' => DB::table('supplier_products')->count(),
                    'Atributos' => DB::table('supplier_product_attributes')->count(),
                    'Chats' => DB::table('supplier_product_chats')->count(),
                ],
                'total' => DB::table('supplier_products')->count()
                         + DB::table('supplier_product_attributes')->count()
                         + DB::table('supplier_product_chats')->count(),
            ],
            'suppliers' => [
                'label' => 'Proveedores',
                'description' => 'Proveedores y sus fuentes de datos',
                'icon' => 'fas fa-truck',
                'tables' => [
                    'Proveedores' => DB::table('suppliers')->count(),
                    'Fuentes' => DB::table('supplier_sources')->count(),
                ],
                'total' => DB::table('suppliers')->count()
                         + DB::table('supplier_sources')->count(),
            ],
            'categories' => [
                'label' => 'Categorías',
                'description' => 'Deportes, familias y subfamilias',
                'icon' => 'fas fa-tags',
                'tables' => [
                    'Deportes' => DB::table('supplier_sports')->count(),
                    'Familias' => DB::table('supplier_categories')->count(),
                    'Subfamilias' => DB::table('supplier_subfamilies')->count(),
                ],
                'total' => DB::table('supplier_sports')->count()
                         + DB::table('supplier_categories')->count()
                         + DB::table('supplier_subfamilies')->count(),
            ],
            'history' => [
                'label' => 'Historial de sincronización',
                'description' => 'Batches, logs de sync, fallos y statuses',
                'icon' => 'fas fa-clock-rotate-left',
                'tables' => [
                    'Batches' => DB::table('supplier_sync_batches')->count(),
                    'Logs sync' => DB::table('supplier_sync_logs')->count(),
                    'Fallos' => DB::table('supplier_sync_failures')->count(),
                ],
                'total' => DB::table('supplier_sync_batches')->count()
                         + DB::table('supplier_sync_logs')->count()
                         + DB::table('supplier_sync_failures')->count()
                         + DB::table('supplier_sync_statuses')->count(),
            ],
        ];
    }
}
