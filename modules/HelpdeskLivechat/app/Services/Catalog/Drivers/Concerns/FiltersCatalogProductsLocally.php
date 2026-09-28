<?php

namespace Modules\HelpdeskLivechat\Services\Catalog\Drivers\Concerns;

use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;

/**
 * Implementación por defecto de CatalogDriver::searchWithFilters() para
 * drivers sin motor de filtros/relajación propio (feed, PrestaShop directo):
 * filtra en memoria el resultado de search(), sin relajar nada si da 0
 * resultados (a diferencia de BridgeCatalogDriver, que delega eso al bridge).
 */
trait FiltersCatalogProductsLocally
{
    /**
     * @param  array<int, CatalogProduct>  $products
     * @param  array{brand?:string,category?:string,price_min?:float,price_max?:float,in_stock?:bool,sort?:string}  $filters
     * @return array<int, CatalogProduct>
     */
    private function filterCatalogProductsLocally(array $products, array $filters): array
    {
        $brand = isset($filters['brand']) && $filters['brand'] !== '' ? mb_strtolower((string) $filters['brand']) : null;
        $category = isset($filters['category']) && $filters['category'] !== '' ? mb_strtolower((string) $filters['category']) : null;
        $priceMin = isset($filters['price_min']) ? (float) $filters['price_min'] : null;
        $priceMax = isset($filters['price_max']) ? (float) $filters['price_max'] : null;
        $inStockOnly = ! empty($filters['in_stock']);

        $filtered = array_values(array_filter(
            $products,
            fn (CatalogProduct $p): bool => $this->matchesLocalFilters($p, $brand, $category, $priceMin, $priceMax, $inStockOnly)
        ));

        return $this->sortCatalogProductsLocally($filtered, (string) ($filters['sort'] ?? 'relevance'));
    }

    private function matchesLocalFilters(
        CatalogProduct $product,
        ?string $brand,
        ?string $category,
        ?float $priceMin,
        ?float $priceMax,
        bool $inStockOnly,
    ): bool {
        if ($inStockOnly && ! $product->available) {
            return false;
        }

        if ($brand !== null && ! str_contains(mb_strtolower((string) $product->brand), $brand)) {
            return false;
        }

        if ($category !== null && ! str_contains(mb_strtolower((string) $product->category), $category)) {
            return false;
        }

        if ($priceMin !== null && ($product->price === null || $product->price < $priceMin)) {
            return false;
        }

        if ($priceMax !== null && ($product->price === null || $product->price > $priceMax)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, CatalogProduct>  $products
     * @return array<int, CatalogProduct>
     */
    private function sortCatalogProductsLocally(array $products, string $sort): array
    {
        if ($sort === 'price_asc' || $sort === 'price_desc') {
            usort($products, fn (CatalogProduct $a, CatalogProduct $b): int => $sort === 'price_asc'
                ? ($a->price ?? 0) <=> ($b->price ?? 0)
                : ($b->price ?? 0) <=> ($a->price ?? 0));
        }

        return $products;
    }
}
