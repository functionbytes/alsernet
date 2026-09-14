<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\SyncFailure\BulkDestroySyncFailureRequest;
use Modules\Supplier\Http\Requests\SyncFailure\BulkRetrySyncFailureRequest;
use Modules\Supplier\Models\Product\Product;
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
        $statusFilter = $request->get('status');

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 15;

        // Fallos activos: pendientes de resolver (cola de trabajo)
        $failuresQuery = SyncFailure::query()->latestFailures()->unresolved();
        $this->applyFailureFilters($failuresQuery, $syncType, $failureType, $batchId, $searchKey);
        if ($statusFilter && in_array($statusFilter, ['pending', 'acknowledged'], true)) {
            $failuresQuery->where('failure_status', $statusFilter);
        }

        $failures = $failuresQuery->paginate($perPage, ['*'], 'failures_page')->withQueryString();
        $this->attachProductReferences($failures->getCollection());

        // Histórico: resueltos/archivados — nunca se borran solos, reporte permanente
        $historyQuery = SyncFailure::query()->latestFailures()->history();
        $this->applyFailureFilters($historyQuery, $syncType, $failureType, $batchId, $searchKey);
        if ($statusFilter && in_array($statusFilter, ['resolved', 'archived'], true)) {
            $historyQuery->where('failure_status', $statusFilter);
        }

        $historyFailures = $historyQuery->paginate($perPage, ['*'], 'history_page')->withQueryString();
        $this->attachProductReferences($historyFailures->getCollection());

        // Conflicts query
        $conflictsQuery = SyncConflict::query()
            ->orderBy('conflict_detected_at', 'desc');

        if ($syncType) {
            $conflictsQuery->byEntityType($syncType);
        }

        $conflicts = $conflictsQuery->paginate($perPage, ['*'], 'conflicts_page')->withQueryString();
        $this->attachConflictReferences($conflicts->getCollection());

        $stats = $this->loadFailureStats();

        return view('supplier::settings.views.sync-failures.index', compact(
            'failures',
            'historyFailures',
            'conflicts',
            'stats',
            'tab',
            'syncType',
            'failureType',
            'batchId',
            'searchKey',
            'statusFilter',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Filtros comunes (tipo de sync, tipo de fallo, batch, búsqueda libre)
     * compartidos entre el listado de fallos activos y el histórico.
     */
    private function applyFailureFilters($query, ?string $syncType, ?string $failureType, ?string $batchId, ?string $searchKey): void
    {
        if ($syncType) {
            $query->byType($syncType);
        }

        if ($failureType) {
            $query->byFailureType($failureType);
        }

        if ($batchId) {
            $query->byBatch((int) $batchId);
        }

        if ($searchKey) {
            // "Referencia" (código del producto) no vive en supplier_sync_failures —
            // se resuelve la lista de erp_id de productos que matchean primero,
            // igual que en la vista de detalle de un batch.
            $matchingErpIds = Product::where('code', 'LIKE', "%{$searchKey}%")->pluck('erp_id')->filter();

            $query->where(function ($q) use ($searchKey, $matchingErpIds) {
                $q->where('error_message', 'LIKE', "%{$searchKey}%")
                    ->orWhere('supplier_id', $searchKey)
                    ->orWhere('erp_id', $searchKey)
                    ->orWhereIn('erp_id', $matchingErpIds)
                    // Referencia "fallback": el código que el ERP mandó pero que nunca
                    // llegó a crear un producto real (ej. fallos "sin proveedor").
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(changed_data, '$.code')) LIKE ?", ["%{$searchKey}%"]);
            });
        }
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
            // Las 4 tarjetas del dashboard reflejan solo la cola activa (pendientes
            // de resolver) — los resueltos/archivados viven aparte en el histórico.
            $failures = SyncFailure::query()->unresolved()->selectRaw(
                'COUNT(*) as total, '
                .'SUM(CASE WHEN retry_count < max_retries THEN 1 ELSE 0 END) as retryable, '
                ."SUM(CASE WHEN failure_type = 'sin_proveedor' THEN 1 ELSE 0 END) as sin_proveedor, "
                ."SUM(CASE WHEN failure_type = 'sin_categoria' THEN 1 ELSE 0 END) as sin_categoria, "
                ."SUM(CASE WHEN failure_type = 'error_api' THEN 1 ELSE 0 END) as error_api, "
                ."SUM(CASE WHEN failure_type = 'error_db' THEN 1 ELSE 0 END) as error_db, "
                ."SUM(CASE WHEN failure_type = 'datos_invalidos' THEN 1 ELSE 0 END) as datos_invalidos"
            )->first();

            $historyTotal = SyncFailure::query()->history()->count();

            $conflicts = SyncConflict::query()->selectRaw(
                'COUNT(*) as total, '
                .'SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) as unresolved, '
                .'SUM(CASE WHEN conflict_detected_at >= ? THEN 1 ELSE 0 END) as recent',
                [now()->subDays(7)]
            )->first();

            return [
                'total_failures' => (int) $failures->total,
                'retryable_failures' => (int) $failures->retryable,
                'total_history' => $historyTotal,
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
     * Adjunta ->reference (código del producto) a cada SyncFailure de la
     * colección. Se resuelve en lote por erp_id contra supplier_products
     * cuando el producto ya existe (p. ej. falló al actualizar); si nunca
     * llegó a crearse (p. ej. "sin proveedor"), se usa el código que el ERP
     * envió en cambios_data — el dato existe igual, solo que aún no vive
     * en ningún producto real.
     */
    private function attachProductReferences(Collection $failures): void
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
     * Get failure details (changed data, context, error info, batch)
     */
    public function show(string $id): JsonResponse
    {
        try {
            $failure = SyncFailure::with('batch')->findOrFail($id);
            $failure->append(['type_name', 'failure_type_name', 'failure_status_name']);
            $this->attachProductReferences(collect([$failure]));

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
                // No se borra: pasa al histórico como "resuelto" para conservar
                // el reporte de todo lo que ha fallado alguna vez.
                $failure->markAsResolved(auth()->id(), 'Reintento manual exitoso');

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
     * "Eliminar" un fallo.
     *
     * Si el fallo todavía está activo (pending/acknowledged), se archiva en
     * vez de borrarse físicamente — pasa al histórico como "archivado" para
     * conservar el reporte de todo lo que ha fallado alguna vez. Si ya
     * estaba en el histórico (resolved/archived), esta es una purga
     * definitiva consciente y sí se borra físicamente.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $failure = SyncFailure::findOrFail($id);

            if (in_array($failure->failure_status, ['resolved', 'archived'], true)) {
                $failure->delete();
                $message = 'Fallo eliminado definitivamente del histórico.';
            } else {
                $failure->update([
                    'failure_status' => 'archived',
                    'resolved_at' => now(),
                    'resolved_by_user_id' => auth()->id(),
                    'resolution_notes' => $failure->resolution_notes ?? 'Descartado manualmente por el usuario',
                ]);
                $message = 'Fallo movido al histórico.';
            }

            $this->flushStatsCache();

            return response()->json([
                'success' => true,
                'message' => $message,
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
     * Bulk "delete" — misma lógica que destroy() pero en lote: los activos
     * se archivan (pasan al histórico), los que ya estaban en el histórico
     * se purgan definitivamente.
     */
    public function bulkDestroy(BulkDestroySyncFailureRequest $request): JsonResponse
    {
        $ids = $request->validated('ids');

        $toArchive = SyncFailure::whereIn('id', $ids)
            ->whereIn('failure_status', ['pending', 'acknowledged'])
            ->get();

        foreach ($toArchive as $failure) {
            $failure->update([
                'failure_status' => 'archived',
                'resolved_at' => now(),
                'resolved_by_user_id' => auth()->id(),
                'resolution_notes' => $failure->resolution_notes ?? 'Descartado manualmente por el usuario',
            ]);
        }

        $purged = SyncFailure::whereIn('id', $ids)
            ->whereIn('failure_status', ['resolved', 'archived'])
            ->whereNotIn('id', $toArchive->pluck('id'))
            ->delete();

        $this->flushStatsCache();

        $archived = $toArchive->count();
        $parts = array_filter([
            $archived ? "{$archived} movido(s) al histórico" : null,
            $purged ? "{$purged} eliminado(s) definitivamente" : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $parts ? implode(', ', $parts).'.' : 'Sin cambios.',
            'deleted' => $archived + $purged,
        ]);
    }

    /**
     * Get conflict details
     */
    public function showConflict(string $id): JsonResponse
    {
        try {
            $conflict = SyncConflict::with('resolvedBy')->findOrFail($id);
            $this->attachConflictReferences(collect([$conflict]));

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
     * Adjunta ->reference (código del producto) a cada SyncConflict de la
     * colección. Solo aplica a conflictos de tipo "product" — "price" y
     * "provider" apuntan a otras tablas sin código de producto asociado.
     */
    private function attachConflictReferences(Collection $conflicts): void
    {
        $productConflicts = $conflicts->filter(fn (SyncConflict $c) => $c->entity_type === SyncConflict::TYPE_PRODUCT);
        $erpIds = $productConflicts->pluck('erp_id')->filter()->unique()->values();

        $productsByErpId = $erpIds->isNotEmpty()
            ? Product::whereIn('erp_id', $erpIds)->pluck('code', 'erp_id')
            : collect();

        foreach ($conflicts as $conflict) {
            $conflict->reference = $conflict->entity_type === SyncConflict::TYPE_PRODUCT && $conflict->erp_id
                ? ($productsByErpId[$conflict->erp_id] ?? null)
                : null;
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
