<?php

namespace Modules\Supplier\Services\Integrations;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Core\Models\Setting;
use Modules\Supplier\Models\Sync\SyncLog;

/**
 * Sincroniza productos de proveedores desde ERP.
 *
 * Consume `/api/erp/suppliers/{id}/products` y sincroniza:
 *   ARTIPROV  → supplier_products        (erp_id = product.id)
 *   ARTICULO  → supplier_product_attributes (erp_id = attribute.id)
 */
class ErpProviderProductSyncService
{
    protected string $erpBaseUrl;

    public function __construct()
    {
        $base = Setting::get('supplier.erp_internal_url', config('supplier.erp_internal_url', 'http://nginx'));
        $this->erpBaseUrl = rtrim($base, '/').'/api/erp';
    }

    /**
     * Sincronizar productos de un proveedor específico
     *
     * @return array{success: bool, products: int, attributes: int, errors: array}
     */
    public function syncProviderProducts(int $erpProviderId, ?SyncBatch $batch = null, ?int $limit = null, array $filterCriteria = []): array
    {

        $stats = ['success' => false, 'products' => 0, 'attributes' => 0, 'errors' => []];

        try {
            $supplier = Supplier::where('erp_id', $erpProviderId)->first();

            if (! $supplier) {
                throw new Exception("Supplier not found: {$erpProviderId}. Sync suppliers first.");
            }

            $response = Http::timeout(60)->get("{$this->erpBaseUrl}/suppliers/{$erpProviderId}/products");

            if (! $response->successful()) {
                throw new Exception("ERP API error: {$response->status()} - {$response->body()}");
            }

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                throw new Exception($data['error'] ?? 'Unknown API error');
            }

            $responseData = $data['data'] ?? $data;
            $products     = $responseData['products'] ?? $data['products'] ?? [];

            // Apply filters
            $products = $this->applyProductFilters($products, $filterCriteria);

            if ($limit !== null && $limit > 0) {
                $products = array_slice($products, 0, $limit);
            }

            if ($batch && $batch->total_items === 0) {
                $batch->update(['total_items' => count($products)]);
            }

            // Auto-upsert sports, families and subfamilies from the response
            // so products can always be linked to their hierarchy
            $this->upsertHierarchyFromResponse($responseData);

            // Pre-load maps to avoid N+1 inside the loop
            $erpCategoryIds = $this->collectErpCategoryIds($products);
            $categoryMap = $erpCategoryIds === []
                ? collect()
                : Category::whereIn('erp_id', $erpCategoryIds)->pluck('id', 'erp_id');

            $erpSubfamilyIds = $this->collectErpSubfamilyIds($products);
            $subfamilyMap = $erpSubfamilyIds === []
                ? collect()
                : Subfamily::whereIn('erp_id', $erpSubfamilyIds)->pluck('id', 'erp_id');

            $erpSportIds = $this->collectErpSportIds($products);
            $sportMap = $erpSportIds === []
                ? collect()
                : Sport::whereIn('erp_id', $erpSportIds)->pluck('id', 'erp_id');

            $logBuffer = [];

            foreach ($products as $productData) {
                $erpArtiProvId = $productData['id'] ?? null;

                try {
                    // Skip products already synced in a previous run.
                    if ($erpArtiProvId && $batch && $this->wasAlreadySynced($erpArtiProvId)) {
                        $logBuffer[] = $this->buildLogRow($batch, [
                            'entity_type' => 'product',
                            'erp_id' => $erpArtiProvId,
                            'action' => 'skip',
                            'result' => 'skipped',
                            'message' => 'Ya sincronizado previamente',
                        ]);
                        $batch->incrementProcessedItems();

                        continue;
                    }

                    $existsBefore = $erpArtiProvId
                        ? Product::where('erp_id', $erpArtiProvId)->exists()
                        : false;

                    $product = $this->syncProduct($supplier, $productData, $categoryMap, $subfamilyMap, $sportMap);
                    if ($product) {
                        $stats['products']++;

                        if ($batch) {
                            $logBuffer[] = $this->buildLogRow($batch, [
                                'entity_type' => 'product',
                                'entity_id'   => $product->id,
                                'erp_id'      => $erpArtiProvId,
                                'action'      => $existsBefore ? 'update' : 'create',
                                'result'      => 'success',
                            ]);
                        }
                    }

                    foreach ($productData['attributes'] ?? [] as $attrData) {
                        $this->syncProductAttribute($product, $attrData, $categoryMap, $subfamilyMap, $sportMap);
                        $stats['attributes']++;
                    }

                    $batch?->incrementProcessedItems();
                } catch (Exception $e) {
                    $stats['errors'][] = "Product {$erpArtiProvId}: {$e->getMessage()}";
                    Log::error('Provider product sync error', [
                        'supplier_id' => $supplier->id,
                        'error' => $e->getMessage(),
                    ]);

                    if ($batch) {
                        $logBuffer[] = $this->buildLogRow($batch, [
                            'entity_type' => 'product',
                            'erp_id' => $erpArtiProvId,
                            'action' => 'update',
                            'result' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                    }

                    $batch?->incrementFailedItems();
                }
            }

            $this->flushLogBuffer($logBuffer);

            $stats['success'] = true;
            Log::info('Provider products sync completed', $stats);
        } catch (Exception $e) {
            Log::error('Provider products sync failed', [
                'erp_provider_id' => $erpProviderId,
                'error' => $e->getMessage(),
            ]);
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Collect every ERP category id referenced by a product or its attributes
     * so we can pre-load the category map and avoid N+1 lookups inside the loop.
     */
    protected function collectErpCategoryIds(array $products): array
    {
        $ids = [];

        foreach ($products as $productData) {
            $candidate = $productData['categorie']
                ?? $productData['category_id']
                ?? $productData['erp_category_id']
                ?? $productData['attributes'][0]['categorie']
                ?? null;

            if ($candidate) {
                $ids[] = $candidate;
            }

            foreach ($productData['attributes'] ?? [] as $attrData) {
                if (! empty($attrData['categorie'])) {
                    $ids[] = $attrData['categorie'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Resolve a Category id from the pre-loaded map, falling back to a single
     * query only when the map is missing or has no entry for the given ERP id.
     */
    protected function resolveCategoryId($erpCategoryId, ?Collection $categoryMap): ?int
    {
        if (! $erpCategoryId) {
            return null;
        }

        if ($categoryMap !== null && $categoryMap->has($erpCategoryId)) {
            return (int) $categoryMap->get($erpCategoryId);
        }

        return Category::where('erp_id', $erpCategoryId)->value('id');
    }

    /**
     * Generic map resolver — returns local id from a pre-loaded erp_id → id map.
     */
    protected function resolveFromMap($erpId, ?Collection $map): ?int
    {
        if (! $erpId || $map === null) {
            return null;
        }

        return $map->has($erpId) ? (int) $map->get($erpId) : null;
    }

    protected function collectErpSubfamilyIds(array $products): array
    {
        $ids = [];

        foreach ($products as $productData) {
            foreach ($productData['attributes'] ?? [] as $attrData) {
                if (! empty($attrData['subfamily_id'])) {
                    $ids[] = $attrData['subfamily_id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function collectErpSportIds(array $products): array
    {
        $ids = [];

        foreach ($products as $productData) {
            foreach ($productData['attributes'] ?? [] as $attrData) {
                if (! empty($attrData['sport_id'])) {
                    $ids[] = $attrData['sport_id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Check whether a product with the given ERP ARTIPROV ID has already been synced.
     */
    protected function wasAlreadySynced(int $erpArtiProvId): bool
    {
        return Product::where('erp_id', $erpArtiProvId)
            ->whereNotNull('last_sync_at')
            ->exists();
    }

    /**
     * Build a SyncLog insert row with the columns SyncLog::insert() needs
     * (uid + timestamps) plus sensible defaults for the unset columns.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function buildLogRow(SyncBatch $batch, array $attributes): array
    {
        $now = now();

        return array_merge([
            'uid' => (string) Str::ulid(),
            'batch_id' => $batch->id,
            'entity_id' => null,
            'erp_id' => null,
            'message' => null,
            'error_message' => null,
            'retry_count' => 0,
            'triggered_by' => 'sync_job',
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes);
    }

    /**
     * Persist any buffered SyncLog rows in a single insert and reset the buffer.
     *
     * @param  list<array<string, mixed>>  $buffer
     */
    protected function flushLogBuffer(array &$buffer): void
    {
        if ($buffer === []) {
            return;
        }

        SyncLog::insert($buffer);
        $buffer = [];
    }

    /**
     * Sincroniza un ARTIPROV → supplier_products
     * Endpoint: product.id = idartiprov
     */
    protected function syncProduct(
        Supplier $supplier,
        array $data,
        ?Collection $categoryMap = null,
        ?Collection $subfamilyMap = null,
        ?Collection $sportMap = null,
    ): ?Product {
        $erpArtiProvId = $data['id'] ?? null;

        if (! $erpArtiProvId) {
            return null;
        }

        $product = Product::withTrashed()->where('erp_id', $erpArtiProvId)->first()
            ?? new Product;

        if ($product->trashed()) {
            $product->restore();
        }

        $firstAttr = $data['attributes'][0] ?? [];

        $erpCategoryId = $data['categorie']
            ?? $data['category_id']
            ?? $data['erp_category_id']
            ?? $firstAttr['categorie']
            ?? null;
        $categoryId = $this->resolveCategoryId($erpCategoryId, $categoryMap);

        $erpSubfamilyId = $firstAttr['subfamily_id'] ?? null;
        $subfamilyId = $this->resolveFromMap($erpSubfamilyId, $subfamilyMap);

        $erpSportId = $firstAttr['sport_id'] ?? null;
        $sportId = $this->resolveFromMap($erpSportId, $sportMap);

        $product->fill([
            'erp_id' => $erpArtiProvId,
            'supplier_id' => $supplier->id,
            'category_id' => $categoryId,
            'erp_category_id' => $erpCategoryId,
            'subfamily_id' => $subfamilyId,
            'erp_subfamily_id' => $erpSubfamilyId,
            'sport_id' => $sportId,
            'erp_sport_id' => $erpSportId,
            'code' => $data['code'] ?? null,
            'name' => $data['description'] ?? null,
            'available' => (bool) ($data['available'] ?? true),
            'is_default' => (bool) ($data['default'] ?? false),
            'web_published' => (bool) ($data['web'] ?? false),
            'last_sync_at' => now(),
        ]);

        $product->save();

        return $product;
    }

    /**
     * Sincroniza un ARTICULO → supplier_product_attributes
     * Endpoint: attribute.id = idarticulo, attribute.categorie = idfamilia_cl, attribute.grupo = idgrupo_cl
     */
    protected function syncProductAttribute(
        ?Product $product,
        array $data,
        ?Collection $categoryMap = null,
        ?Collection $subfamilyMap = null,
        ?Collection $sportMap = null,
    ): void {
        $erpArticuloId = $data['id'] ?? null;

        if (! $erpArticuloId || ! $product) {
            return;
        }

        $attribute = ProductAttribute::withTrashed()->where('erp_id', $erpArticuloId)->first()
            ?? new ProductAttribute;

        if ($attribute->trashed()) {
            $attribute->restore();
        }

        $erpCategoryId = $data['categorie'] ?? null;
        $categoryId = $this->resolveCategoryId($erpCategoryId, $categoryMap);

        $erpSubfamilyId = $data['subfamily_id'] ?? null;
        $subfamilyId = $this->resolveFromMap($erpSubfamilyId, $subfamilyMap);

        $erpSportId = $data['sport_id'] ?? null;
        $sportId = $this->resolveFromMap($erpSportId, $sportMap);

        $attribute->fill([
            'erp_id' => $erpArticuloId,
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'erp_category_id' => $erpCategoryId,
            'erp_group_id' => $data['grupo'] ?? null,
            'subfamily_id' => $subfamilyId,
            'erp_subfamily_id' => $erpSubfamilyId,
            'sport_id' => $sportId,
            'erp_sport_id' => $erpSportId,
            'code' => $data['code'] ?? null,
            'code_secundary' => $data['code_secundary'] ?? null,
            'ean13' => $data['ean13'] ?? null,
            'upc' => $data['upc'] ?? null,
            'reference' => $data['reference'] ?? null,
            'name' => $data['name'] ?? $data['description'] ?? null,
            'available' => (bool) ($data['available'] ?? true),
            'web_published' => (bool) ($data['web'] ?? false),
            'erp_created_at' => $data['created'] ?? null,
            'erp_updated_at' => $data['updated'] ?? null,
            'last_sync_at' => now(),
        ]);

        $attribute->save();
    }

    public function syncAllProviderProducts(?SyncBatch $batch = null, ?int $limit = null, array $filterCriteria = []): array
    {
        $overallStats = [
            'success' => false,
            'providers_processed' => 0,
            'total_products' => 0,
            'errors' => [],
        ];

        try {
            $suppliers = Supplier::where('available', true)->get();
            $remainingLimit = $limit;

            foreach ($suppliers as $supplier) {
                try {
                    $stats = $this->syncProviderProducts($supplier->erp_id, $batch, $remainingLimit, $filterCriteria);
                    $overallStats['providers_processed']++;
                    $overallStats['total_products'] += $stats['products'];

                    if ($remainingLimit !== null) {
                        $remainingLimit -= $stats['products'];
                        if ($remainingLimit <= 0) {
                            break;
                        }
                    }

                    if (! empty($stats['errors'])) {
                        $overallStats['errors'] = array_merge($overallStats['errors'], $stats['errors']);
                    }
                } catch (Exception $e) {
                    $overallStats['errors'][] = "Supplier {$supplier->erp_id}: {$e->getMessage()}";
                }
            }

            $overallStats['success'] = true;
        } catch (Exception $e) {
            $overallStats['errors'][] = $e->getMessage();
        }

        return $overallStats;
    }

    /**
     * Apply filter criteria to products array
     */
    protected function applyProductFilters(array $products, array $filterCriteria = []): array
    {
        if (empty($filterCriteria)) {
            return $products;
        }

        $dateFrom = $filterCriteria['date_from'] ?? null;
        $dateField = $filterCriteria['date_field'] ?? 'creation';
        $webFilter = $filterCriteria['web_filter'] ?? '2';
        $descriptionEmpty = $filterCriteria['description_empty'] ?? false;

        return array_filter($products, function ($product) use ($dateFrom, $dateField, $webFilter, $descriptionEmpty) {
            // Get the first attribute to check dates (they're stored in attributes, not product)
            $firstAttr = $product['attributes'][0] ?? [];

            // Date filter: check against attribute dates
            if ($dateFrom) {
                $dateFieldKey = ($dateField === 'modification') ? 'updated' : 'created';
                $attrDate = $firstAttr[$dateFieldKey] ?? null;

                if ($attrDate && strtotime($attrDate) < strtotime($dateFrom)) {
                    return false;
                }
            }

            // Description empty filter: check product name
            if ($descriptionEmpty && empty($product['description'])) {
                return false;
            }

            // Web filter: '1' = only web, '2' = all (no filter)
            if ($webFilter === '1' && ! ($product['web'] ?? false)) {
                return false;
            }

            return true;
        });
    }

    /**
     * Upsert sports, families (categorías) and subfamilies from the ERP response
     * so that products can always be linked to their full hierarchy.
     */
    protected function upsertHierarchyFromResponse(array $data): void
    {
        // --- Deportes ---
        foreach ($data['sports'] ?? [] as $s) {
            if (empty($s['id'])) continue;
            Sport::updateOrCreate(
                ['erp_id' => $s['id']],
                [
                    'name'      => $s['description'] ?? "Deporte {$s['id']}",
                    'available' => (bool) ($s['available'] ?? true),
                ]
            );
        }

        // --- Familias (categories en el response = familias en BD) ---
        $sportIdMap = Sport::whereNotNull('erp_id')->pluck('id', 'erp_id');

        foreach ($data['categories'] ?? [] as $f) {
            if (empty($f['id'])) continue;
            $sportId = isset($f['sport_id']) ? ($sportIdMap->get($f['sport_id'])) : null;
            Category::updateOrCreate(
                ['erp_id' => $f['id']],
                [
                    'name'               => $f['description'] ?? "Familia {$f['id']}",
                    'sport_id'           => $sportId,
                    'erp_sport_id'       => $f['sport_id'] ?? null,
                    'erp_categoria_id'   => $f['categoria_id'] ?? null,
                    'erp_categoria_name' => $f['categoria_name'] ?? null,
                    'available'          => (bool) ($f['available'] ?? true),
                    'last_sync_at'       => now(),
                ]
            );
        }

        // --- Subfamilias ---
        $categoryIdMap = Category::whereNotNull('erp_id')->pluck('id', 'erp_id');

        foreach ($data['subfamilies'] ?? [] as $s) {
            if (empty($s['id'])) continue;
            $categoryId = isset($s['family_id']) ? ($categoryIdMap->get($s['family_id'])) : null;
            Subfamily::updateOrCreate(
                ['erp_id' => $s['id']],
                [
                    'name'             => $s['description'] ?? "Subfamilia {$s['id']}",
                    'category_id'      => $categoryId,
                    'erp_category_id'  => $s['family_id'] ?? null,
                    'available'        => (bool) ($s['available'] ?? true),
                    'last_sync_at'     => now(),
                ]
            );
        }
    }
}
