<?php

namespace Modules\Supplier\Services\Integrations;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * Sincroniza proveedores desde ERP Oracle → tabla suppliers
 * Usa el endpoint interno /api/erp/suppliers/{id}/detailed
 */
class ErpProviderSyncService
{
    protected string $internalBaseUrl;

    protected int $pageSize = 100;

    public function __construct()
    {
        $this->internalBaseUrl = config('supplier.erp_internal_url', 'http://nginx').'/api/erp';
    }

    /**
     * @return array{success: bool, synced: int, created: int, updated: int, errors: array}
     */
    public function syncAllProviders(?SyncBatch $batch = null): array
    {
        $stats = ['success' => false, 'synced' => 0, 'created' => 0, 'updated' => 0, 'errors' => []];

        try {
            Log::info('Starting provider sync from ERP');

            $offset = 0;
            $hasMore = true;

            while ($hasMore) {
                $response = Http::timeout(30)->get("{$this->internalBaseUrl}/suppliers", [
                    'limit' => $this->pageSize,
                    'offset' => $offset,
                ]);

                if (! $response->successful()) {
                    $stats['errors'][] = "ERP API error: {$response->status()}";
                    break;
                }

                $data = $response->json();
                $providers = $data['data'] ?? [];
                $total = $data['total'] ?? 0;

                if ($batch && $batch->total_items === 0 && $total > 0) {
                    $batch->update(['total_items' => $total]);
                }

                if (empty($providers)) {
                    break;
                }

                foreach ($providers as $row) {
                    try {
                        $result = $this->syncProvider($row);
                        $stats['synced']++;
                        $result['created'] ? $stats['created']++ : $stats['updated']++;
                        $batch?->incrementProcessedItems();
                    } catch (Exception $e) {
                        $stats['errors'][] = "Provider {$row['id']}: {$e->getMessage()}";
                        Log::error('Provider sync error', ['provider_id' => $row['id'], 'error' => $e->getMessage()]);
                        $batch?->incrementFailedItems();
                    }
                }

                $offset += $this->pageSize;
                $hasMore = ($offset < $total);
            }

            $stats['success'] = true;
            Log::info('Provider sync completed', $stats);

        } catch (Exception $e) {
            Log::error('Provider sync failed', ['error' => $e->getMessage()]);
            $stats['errors'][] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Sincroniza un proveedor por su ID de ERP usando el endpoint /detailed.
     * Persiste directamente los deportes, categorías y productos incluidos en la respuesta.
     */
    public function syncProviderById(int $erpId): array
    {
        $response = Http::timeout(30)->get("{$this->internalBaseUrl}/suppliers/{$erpId}/detailed");

        if (! $response->successful()) {
            throw new Exception("ERP API error {$response->status()} para proveedor {$erpId}");
        }

        $data = $response->json();

        if (! ($data['success'] ?? false) || empty($data['data'])) {
            throw new Exception("Proveedor {$erpId} no encontrado en el ERP");
        }

        $providerData = $data['data'];

        $result = $this->syncProvider($providerData);
        $supplier = $result['supplier'];

        $categories = $providerData['categories'] ?? [];
        $products = $providerData['products'] ?? [];

        $sportIdByErpId = $this->syncSportsFromData($providerData['sports'] ?? []);
        $this->syncCategoriesFromData($categories, $sportIdByErpId);

        // Pre-load the category map once so per-product/per-attribute lookups don't hit the DB in a loop.
        $categoryIdByErpId = $this->loadCategoryMap($this->collectErpCategoryIds($products));

        $this->syncProductsFromData($supplier, $products, $categoryIdByErpId);
        $this->linkSupplierCategoriesFromData($supplier, $categories);

        return ['created' => $result['created'], 'supplier' => $supplier];
    }

    /**
     * Upserts the given sports and returns a map of erp_id => local id.
     *
     * @return Collection<int|string, int>
     */
    private function syncSportsFromData(array $sports): Collection
    {
        $map = Sport::query()->pluck('id', 'erp_id');

        foreach ($sports as $row) {
            $erpId = $row['id'] ?? null;

            if (! $erpId) {
                continue;
            }

            try {
                $sport = Sport::firstOrNew(['erp_id' => $erpId]);
                $sport->fill([
                    'name' => $row['description'] ?? null,
                    'short_name' => $row['description_short'] ?? null,
                    'available' => (bool) ($row['available'] ?? true),
                    'last_sync_at' => now(),
                ]);
                $sport->save();

                $map->put($erpId, $sport->id);
            } catch (Exception $e) {
                Log::warning("Sport sync skipped for ERP ID {$erpId}: {$e->getMessage()}");
            }
        }

        return $map;
    }

    /**
     * @param  Collection<int|string, int>  $sportIdByErpId
     */
    private function syncCategoriesFromData(array $categories, Collection $sportIdByErpId): void
    {
        foreach ($categories as $row) {
            $erpId = $row['id'] ?? null;

            if (! $erpId) {
                continue;
            }

            try {
                $erpSportId = $row['sport_id'] ?? null;
                $sportId = $erpSportId ? $sportIdByErpId->get($erpSportId) : null;

                $category = Category::firstOrNew(['erp_id' => $erpId]);
                $category->fill([
                    'sport_id' => $sportId,
                    'erp_sport_id' => $erpSportId,
                    'name' => $row['description'] ?? null,
                    'short_name' => $row['description_short'] ?? null,
                    'available' => (bool) ($row['available'] ?? true),
                    'erp_created_at' => $row['created'] ?? null,
                    'erp_updated_at' => $row['updated'] ?? null,
                    'last_sync_at' => now(),
                ]);
                $category->save();
            } catch (Exception $e) {
                Log::warning("Category sync skipped for ERP ID {$erpId}: {$e->getMessage()}");
            }
        }
    }

    /**
     * @param  Collection<int|string, int>  $categoryIdByErpId
     */
    private function syncProductsFromData(Supplier $supplier, array $products, Collection $categoryIdByErpId): void
    {
        foreach ($products as $productData) {
            // id ahora es el idmodelo (agrupado por modelo)
            $erpModeloId = $productData['id'] ?? null;

            if (! $erpModeloId) {
                continue;
            }

            try {

                $product = Product::withTrashed()->where('erp_id', $erpModeloId)->first()
                    ?? new Product;

                if ($product->trashed()) {
                    $product->restore();
                }

                $firstAttr = $productData['attributes'][0] ?? [];
                $erpCategoryId = $productData['categorie'] ?? $firstAttr['categorie'] ?? null;
                $erpGroupId = $productData['grupo'] ?? $firstAttr['grupo'] ?? null;

                // Buscar Categoría en la BD y extraer datos en cascada (Sport, Subfamily)
                $categoryId = null;
                $sportId = null;
                $subfamilyId = null;
                $erpSportId = null;
                $erpSubfamilyId = null;

                if ($erpCategoryId) {
                    $category = Category::where('erp_id', $erpCategoryId)->first();

                    if ($category) {
                        $categoryId = $category->id;
                        $sportId = $category->sport_id;
                        $erpSportId = $category->erp_sport_id;

                        // Extraer la primera Subfamilia relacionada con esta Categoría
                        $subfamily = $category->subfamilies()->first();
                        if ($subfamily) {
                            $subfamilyId = $subfamily->id;
                            $erpSubfamilyId = $subfamily->erp_id;
                        }
                    }
                }

                $product->fill([
                    'erp_id' => $erpModeloId,
                    'supplier_id' => $supplier->id,
                    'category_id' => $categoryId,
                    'erp_category_id' => $erpCategoryId,
                    'sport_id' => $sportId,
                    'erp_sport_id' => $erpSportId,
                    'subfamily_id' => $subfamilyId,
                    'erp_subfamily_id' => $erpSubfamilyId,
                    'erp_model_id' => $erpGroupId,
                    'code' => $productData['code'] ?? null,
                    'name' => $productData['name'] ?? $productData['description'] ?? null,
                    'available' => (bool) ($productData['available'] ?? true),
                    'is_default' => (bool) ($productData['default'] ?? false),
                    'web_published' => (bool) ($productData['web'] ?? false),
                    'last_sync_at' => now(),
                ]);

                $product->save();

                foreach ($productData['attributes'] ?? [] as $attrData) {
                    $this->syncAttributeFromData($product, $attrData, $categoryIdByErpId);
                }
            } catch (Exception $e) {
                Log::warning("Product sync skipped for ERP modelo ID {$erpModeloId}: {$e->getMessage()}");
            }
        }
    }

    /**
     * @param  Collection<int|string, int>  $categoryIdByErpId
     */
    private function syncAttributeFromData(Product $product, array $data, Collection $categoryIdByErpId): void
    {
        $erpArticuloId = $data['id'] ?? null;

        if (! $erpArticuloId) {
            return;
        }

        $attribute = ProductAttribute::withTrashed()->where('erp_id', $erpArticuloId)->first()
            ?? new ProductAttribute;

        if ($attribute->trashed()) {
            $attribute->restore();
        }

        $erpCategoryId = $data['categorie'] ?? null;

        // Buscar Categoría en la BD y extraer datos en cascada (Sport, Subfamily)
        $categoryId = null;
        $sportId = null;
        $subfamilyId = null;
        $erpSportId = null;
        $erpSubfamilyId = null;

        if ($erpCategoryId) {
            $category = Category::where('erp_id', $erpCategoryId)->first();

            if ($category) {
                $categoryId = $category->id;
                $sportId = $category->sport_id;
                $erpSportId = $category->erp_sport_id;

                // Extraer la primera Subfamilia relacionada con esta Categoría
                $subfamily = $category->subfamilies()->first();
                if ($subfamily) {
                    $subfamilyId = $subfamily->id;
                    $erpSubfamilyId = $subfamily->erp_id;
                }
            }
        }

        $attribute->fill([
            'erp_id' => $erpArticuloId,
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'erp_category_id' => $erpCategoryId,
            'sport_id' => $sportId,
            'erp_sport_id' => $erpSportId,
            'subfamily_id' => $subfamilyId,
            'erp_subfamily_id' => $erpSubfamilyId,
            'erp_group_id' => $data['grupo'] ?? null,
            'code' => $data['code'] ?? null,
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

    /**
     * Collect every ERP category id referenced by a product or its attributes.
     *
     * @return list<int|string>
     */
    private function collectErpCategoryIds(array $products): array
    {
        $ids = [];

        foreach ($products as $productData) {
            $firstAttr = $productData['attributes'][0] ?? [];
            $candidate = $productData['categorie'] ?? $firstAttr['categorie'] ?? null;

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
     * @param  list<int|string>  $erpCategoryIds
     * @return Collection<int|string, int>
     */
    private function loadCategoryMap(array $erpCategoryIds): Collection
    {
        if ($erpCategoryIds === []) {
            return collect();
        }

        return Category::whereIn('erp_id', $erpCategoryIds)->pluck('id', 'erp_id');
    }

    private function linkSupplierCategoriesFromData(Supplier $supplier, array $categories): void
    {
        if (empty($categories)) {
            return;
        }

        $erpIds = array_filter(array_column($categories, 'id'));
        $localCategories = Category::whereIn('erp_id', $erpIds)->get();

        foreach ($localCategories as $category) {
            $supplier->categories()->syncWithoutDetaching([
                $category->id => [
                    'is_active' => true,
                    'is_primary' => false,
                    'priority' => 0,
                ],
            ]);
        }
    }

    /**
     * Persiste un proveedor mapeando los campos del endpoint ERP al modelo local
     */
    public function syncProvider(array $row): array
    {
        $erpId = $row['id'];

        $supplier = Supplier::withTrashed()->where('erp_id', $erpId)->first()
            ?? new Supplier;

        $created = ! $supplier->exists;

        if ($supplier->trashed()) {
            $supplier->restore();
        }

        $supplier->fill([
            'erp_id' => $erpId,
            'label' => $row['label'] ?? null,
            'cif' => $row['cif'] ?? null,
            'email' => $row['email'] ?? null,
            'available' => (bool) ($row['available'] ?? true),
            'last_sync_at' => now(),
        ]);

        $supplier->save();

        return ['created' => $created, 'supplier' => $supplier];
    }
}
