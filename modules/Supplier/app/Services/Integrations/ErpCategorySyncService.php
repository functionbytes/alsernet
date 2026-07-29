<?php

namespace Modules\Supplier\Services\Integrations;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Core\Models\Setting;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * Sincroniza deportes y categorías desde ERP Oracle:
 *   DEPORTE_CL → supplier_sports
 *   FAMILIA_CL → supplier_categories
 */
class ErpCategorySyncService
{
    protected string $erpBaseUrl;

    protected int $pageSize = 100;

    public function __construct()
    {
        $base = Setting::get('supplier.erp_internal_url', config('supplier.erp_internal_url', 'http://nginx'));
        $this->erpBaseUrl = rtrim($base, '/').'/api/erp';
    }

    /**
     * @return array{success: bool, sports: array, categories: array, subfamilies: array, errors: array}
     */
    public function syncAllHierarchy(?SyncBatch $batch = null): array
    {
        $results = [
            'success' => false,
            'sports' => ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []],
            'categories' => ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []],
            'subfamilies' => ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []],
            'errors' => [],
        ];

        try {
            Log::info('Starting category hierarchy sync from ERP');

            $syncStartedAt = now();

            $results['sports']      = $this->syncSports($batch, $syncStartedAt);
            $results['categories']  = $this->syncCategories($batch, $syncStartedAt);
            $results['subfamilies'] = $this->syncSubfamilies($batch, $syncStartedAt);

            $results['success'] = true;

            Log::info('Category hierarchy sync completed', $results);
        } catch (Exception $e) {
            Log::error('Category hierarchy sync failed', ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /** Quita el prefijo "T." que el ERP antepone a todos los nombres de categorías. */
    private function stripPrefix(?string $name): ?string
    {
        return $name ? preg_replace('/^T\./i', '', $name) : $name;
    }

    protected function syncSports(?SyncBatch $batch, Carbon $syncStartedAt): array
    {
        // No hay endpoint dedicado de deportes en el ERP interno.
        // Los deportes llegan embebidos en el endpoint de productos por proveedor.
        // Devolvemos los que ya existen en BD sin hacer llamada HTTP.
        $existing = Sport::count();
        Log::info('Sports sync skipped (no ERP endpoint) — using existing records', ['count' => $existing]);

        return ['synced' => $existing, 'created' => 0, 'updated' => 0, 'errors' => []];
    }

    protected function syncCategories(?SyncBatch $batch, Carbon $syncStartedAt): array
    {
        $stats = ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []];

        try {
            $offset = 0;

            do {
                $response = Http::timeout(30)->get("{$this->erpBaseUrl}/families", [
                    'limit' => $this->pageSize,
                    'offset' => $offset,
                ]);

                if (! $response->successful()) {
                    $stats['errors'][] = "API error: {$response->status()}";
                    break;
                }

                $data = $response->json();
                $rows = $data['data'] ?? [];
                $hasMore = $data['pagination']['hasMore'] ?? false;

                foreach ($rows as $row) {
                    $erpId = $row['idfamilia_cl'] ?? $row['id'] ?? null;

                    try {
                        if (! $erpId) {
                            continue;
                        }

                        $category = Category::firstOrNew(['erp_id' => $erpId]);
                        $created = ! $category->exists;

                        $category->fill([
                            'name'         => $this->stripPrefix($row['descripcion'] ?? $row['description'] ?? null),
                            'short_name'   => $this->stripPrefix($row['desc_corta'] ?? $row['description_short'] ?? null),
                            'available'    => (bool) ($row['estado'] ?? $row['available'] ?? true),
                            'last_sync_at' => now(),
                        ]);

                        $category->save();

                        $stats['synced']++;
                        $created ? $stats['created']++ : $stats['updated']++;

                        $batch?->incrementProcessedItems();
                    } catch (Exception $e) {
                        $stats['errors'][] = "Category {$erpId}: {$e->getMessage()}";
                        Log::error('Category sync error', ['id' => $erpId, 'error' => $e->getMessage()]);
                    }
                }

                $offset += $this->pageSize;
            } while ($hasMore);

            Log::info('Categories synced', $stats);
        } catch (Exception $e) {
            Log::error('Categories sync failed', ['error' => $e->getMessage()]);
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    protected function syncSubfamilies(?SyncBatch $batch, Carbon $syncStartedAt): array
    {
        $stats = ['synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []];

        try {
            $offset = 0;
            $categoryIdByErpId = Category::query()->pluck('id', 'erp_id');

            do {
                $response = Http::timeout(30)->get("{$this->erpBaseUrl}/subfamilies", [
                    'limit' => $this->pageSize,
                    'offset' => $offset,
                ]);

                if (! $response->successful()) {
                    $stats['errors'][] = "API error: {$response->status()}";
                    break;
                }

                $data = $response->json();
                $rows = $data['data'] ?? [];
                $hasMore = $data['pagination']['hasMore'] ?? false;

                foreach ($rows as $row) {
                    try {
                        $erpId = $row['idsubfamilia_cl'] ?? $row['id'] ?? null;
                        $erpCategoryId = $row['idfamilia_cl'] ?? $row['family_id'] ?? null;

                        if (! $erpId) {
                            continue;
                        }

                        $subfamily = Subfamily::firstOrNew(['erp_id' => $erpId]);
                        $created = ! $subfamily->exists;

                        $categoryId = $erpCategoryId ? $categoryIdByErpId->get($erpCategoryId) : null;

                        $subfamily->fill([
                            'category_id'     => $categoryId,
                            'erp_category_id' => $erpCategoryId,
                            'name'            => $this->stripPrefix($row['descripcion'] ?? $row['description'] ?? null),
                            'short_name'      => $this->stripPrefix($row['desc_corta'] ?? $row['description_short'] ?? null),
                            'available'       => (bool) ($row['estado'] ?? $row['available'] ?? true),
                            'last_sync_at'    => now(),
                        ]);

                        $subfamily->save();

                        $stats['synced']++;
                        $created ? $stats['created']++ : $stats['updated']++;

                        $batch?->incrementProcessedItems();
                    } catch (Exception $e) {
                        $stats['errors'][] = "Subfamily {$erpId}: {$e->getMessage()}";
                        Log::error('Subfamily sync error', ['erp_id' => $erpId, 'error' => $e->getMessage()]);
                    }
                }

                $offset += $this->pageSize;
            } while ($hasMore);

            Log::info('Subfamilies synced', $stats);
        } catch (Exception $e) {
            Log::error('Subfamilies sync failed', ['error' => $e->getMessage()]);
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }
}
