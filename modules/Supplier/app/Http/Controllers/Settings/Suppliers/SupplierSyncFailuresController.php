<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\SyncFailure\BulkDestroySyncFailureRequest;
use Modules\Supplier\Http\Requests\SyncFailure\BulkRetrySyncFailureRequest;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\SupplierProductPrice;
use Modules\Supplier\Models\Sync\SyncConflict;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\ErpSyncService;
use Modules\Supplier\Services\Integrations\ErpModelSyncService;

class SupplierSyncFailuresController extends Controller
{
    private const STATS_CACHE_KEY = 'supplier:sync:failures:stats';

    public function __construct(
        protected ErpSyncService $erpSyncService,
        protected ErpModelSyncService $erpModelSyncService,
    ) {}

    /**
     * Display dashboard of sync failures and conflicts
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Fallos de Sincronización';
        $breadcrumb = 'Configuración / Proveedores / Sincronización';

        $tab = $request->get('tab', 'failures');
        $syncType = $request->get('sync_type');
        $failureType = $request->get('failure_type');
        $batchId = $request->get('batch_id');
        $searchKey = $request->get('search');

        // Failures query
        $failuresQuery = SyncFailure::query()->latestFailures();

        if ($syncType) {
            $failuresQuery->byType($syncType);
        }

        if ($failureType) {
            $failuresQuery->byFailureType($failureType);
        }

        if ($batchId) {
            $failuresQuery->byBatch((int) $batchId);
        }

        if ($searchKey) {
            $failuresQuery->where(function ($q) use ($searchKey) {
                $q->where('error_message', 'LIKE', "%{$searchKey}%")
                    ->orWhere('supplier_id', $searchKey)
                    ->orWhere('erp_id', $searchKey);
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 15;

        $failures = $failuresQuery->paginate($perPage, ['*'], 'failures_page')->withQueryString();

        // Conflicts query
        $conflictsQuery = SyncConflict::query()
            ->orderBy('conflict_detected_at', 'desc');

        if ($syncType) {
            $conflictsQuery->byEntityType($syncType);
        }

        $conflicts = $conflictsQuery->paginate($perPage, ['*'], 'conflicts_page')->withQueryString();

        $stats = $this->loadFailureStats();

        return view('supplier::settings.views.sync-failures.index', compact(
            'failures',
            'conflicts',
            'stats',
            'tab',
            'syncType',
            'failureType',
            'batchId',
            'searchKey',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Aggregate failure/conflict counters in two queries and cache them for
     * a couple of minutes — these audit tables are written to constantly by
     * sync jobs, so the dashboard does not need fresh numbers on every load.
     *
     * @return array{
     *     total_failures: int,
     *     retryable_failures: int,
     *     total_conflicts: int,
     *     unresolved_conflicts: int,
     *     recent_conflicts: int,
     *     failures_by_type: array<string, int>
     * }
     */
    private function loadFailureStats(): array
    {
        return Cache::remember(self::STATS_CACHE_KEY, 120, function (): array {
            $failures = SyncFailure::query()->selectRaw(
                'COUNT(*) as total, '
                .'SUM(CASE WHEN retry_count < max_retries THEN 1 ELSE 0 END) as retryable, '
                ."SUM(CASE WHEN failure_type = 'sin_proveedor' THEN 1 ELSE 0 END) as sin_proveedor, "
                ."SUM(CASE WHEN failure_type = 'sin_categoria' THEN 1 ELSE 0 END) as sin_categoria, "
                ."SUM(CASE WHEN failure_type = 'error_api' THEN 1 ELSE 0 END) as error_api, "
                ."SUM(CASE WHEN failure_type = 'error_db' THEN 1 ELSE 0 END) as error_db, "
                ."SUM(CASE WHEN failure_type = 'datos_invalidos' THEN 1 ELSE 0 END) as datos_invalidos"
            )->first();

            $conflicts = SyncConflict::query()->selectRaw(
                'COUNT(*) as total, '
                .'SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) as unresolved, '
                .'SUM(CASE WHEN conflict_detected_at >= ? THEN 1 ELSE 0 END) as recent',
                [now()->subDays(7)]
            )->first();

            return [
                'total_failures' => (int) $failures->total,
                'retryable_failures' => (int) $failures->retryable,
                'total_conflicts' => (int) $conflicts->total,
                'unresolved_conflicts' => (int) $conflicts->unresolved,
                'recent_conflicts' => (int) $conflicts->recent,
                'failures_by_type' => [
                    'sin_proveedor' => (int) $failures->sin_proveedor,
                    'sin_categoria' => (int) $failures->sin_categoria,
                    'error_api' => (int) $failures->error_api,
                    'error_db' => (int) $failures->error_db,
                    'datos_invalidos' => (int) $failures->datos_invalidos,
                ],
            ];
        });
    }

    private function flushStatsCache(): void
    {
        Cache::forget(self::STATS_CACHE_KEY);
    }

    /**
     * Get failure details (changed data, context, error info, batch)
     */
    public function show(string $id): JsonResponse
    {
        try {
            $failure = SyncFailure::with('batch')->findOrFail($id);
            $failure->append(['type_name', 'failure_type_name', 'failure_status_name']);

            return response()->json([
                'success' => true,
                'failure' => $failure,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fallo no encontrado.',
            ], 404);
        }
    }

    /**
     * Retry a specific sync failure
     */
    public function retry(Request $request, string $id): JsonResponse
    {
        try {
            $failure = SyncFailure::findOrFail($id);

            // Always allow retry — user wants to force re-sync
            $failure->increment('retry_count');
            $failure->update(['last_retry_at' => now()]);

            // Attempt to sync again based on type
            $result = match ($failure->sync_type) {
                'price' => $this->retryPriceSync($failure),
                'product' => $this->retryProductSync($failure),
                'provider' => $this->retryProviderSync($failure),
                default => throw new \Exception('Tipo de sincronización desconocido: '.$failure->sync_type),
            };

            $this->flushStatsCache();

            if ($result['success']) {
                $failure->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización reintentada exitosamente.',
                ]);
            }

            $failure->update(['error_message' => $result['error']]);

            return response()->json([
                'success' => false,
                'message' => 'El reintento falló: '.$result['error'],
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error retrying sync failure', [
                'failure_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reintentar',
            ], 500);
        }
    }

    /**
     * Bulk retry multiple failures
     */
    public function bulkRetry(BulkRetrySyncFailureRequest $request): JsonResponse
    {
        $ids = $request->validated('ids');
        $successes = 0;
        $failures = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $response = $this->retry($request, $id);
                $data = json_decode($response->getContent(), true);

                if ($data['success']) {
                    $successes++;
                } else {
                    $failures++;
                    $errors[] = "ID {$id}: {$data['message']}";
                }
            } catch (\Exception $e) {
                $failures++;
                $errors[] = "ID {$id}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Reintentos completados: {$successes} exitosos, {$failures} fallidos.",
            'successes' => $successes,
            'failures' => $failures,
            'errors' => $errors,
        ]);
    }

    /**
     * Delete a sync failure
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $failure = SyncFailure::findOrFail($id);
            $failure->delete();

            $this->flushStatsCache();

            return response()->json([
                'success' => true,
                'message' => 'Fallo eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting sync failure', [
                'failure_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar',
            ], 500);
        }
    }

    /**
     * Bulk delete multiple failures
     */
    public function bulkDestroy(BulkDestroySyncFailureRequest $request): JsonResponse
    {
        $ids = $request->validated('ids');
        $deleted = SyncFailure::whereIn('id', $ids)->delete();

        $this->flushStatsCache();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} fallos eliminados correctamente.",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Get conflict details
     */
    public function showConflict(string $id): JsonResponse
    {
        try {
            $conflict = SyncConflict::with('resolvedBy')->findOrFail($id);

            return response()->json([
                'success' => true,
                'conflict' => $conflict,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conflicto no encontrado.',
            ], 404);
        }
    }

    /**
     * Helper: Retry price sync
     */
    protected function retryPriceSync(SyncFailure $failure): array
    {
        try {
            $price = SupplierProductPrice::find($failure->entity_id);

            if (! $price) {
                return ['success' => false, 'error' => 'Precio no encontrado'];
            }

            $changedFields = array_keys($failure->changed_data);
            $this->erpSyncService->syncPriceToOracle($price, $changedFields);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Helper: Retry product sync
     */
    protected function retryProductSync(SyncFailure $failure): array
    {
        try {
            $result = $this->erpModelSyncService->retryModelFromErp($failure->erp_id);

            if (! $result['success']) {
                return ['success' => false, 'error' => $result['error'] ?? 'Reintento fallido'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Helper: Retry provider sync
     */
    protected function retryProviderSync(SyncFailure $failure): array
    {
        try {
            $provider = Supplier::find($failure->supplier_id);

            if (! $provider) {
                return ['success' => false, 'error' => 'Proveedor no encontrado'];
            }

            $changedFields = array_keys($failure->changed_data);
            $this->erpSyncService->syncProviderToOracle($provider, $changedFields);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
