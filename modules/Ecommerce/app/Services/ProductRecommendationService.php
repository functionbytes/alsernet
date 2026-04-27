<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;

class ProductRecommendationService
{
    /**
     * "Quizás te interese" basado en historial reciente del cliente.
     * Si no hay customer logueado o sin historial, devuelve productos populares.
     */
    public function suggestForCustomer(?Customer $customer = null, int $limit = 8): Collection
    {
        if (! $customer) {
            return $this->popularProducts($limit);
        }

        $cacheKey = "ecommerce_recommendations_customer_{$customer->id}_{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($customer, $limit) {
            $viewedIds = $customer->recentlyViewed()->pluck('ecommerce_products.id')->toArray();

            if (empty($viewedIds)) {
                return $this->popularProducts($limit);
            }

            $categoryIds = DB::table('ecommerce_product_category')
                ->whereIn('product_id', $viewedIds)
                ->pluck('category_id')
                ->unique()
                ->toArray();

            if (empty($categoryIds)) {
                return $this->popularProducts($limit);
            }

            return Product::query()
                ->where('status', 'published')
                ->where('is_variation', false)
                ->whereNotIn('id', $viewedIds)
                ->whereHas('categories', fn ($q) => $q->whereIn('ecommerce_product_categories.id', $categoryIds))
                ->with(['categories', 'brand', 'reviews'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * "Frecuentemente comprados juntos" basado en co-occurrence en órdenes.
     * Encuentra productos que aparecen en órdenes junto al $product dado.
     */
    public function frequentlyBoughtTogether(Product $product, int $limit = 4): Collection
    {
        $cacheKey = "ecommerce_fbt_{$product->id}_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($product, $limit) {
            $orderIds = DB::table('ecommerce_order_items')
                ->where('product_id', $product->id)
                ->pluck('order_id');

            if ($orderIds->isEmpty()) {
                return new Collection;
            }

            $coOccurringIds = DB::table('ecommerce_order_items')
                ->whereIn('order_id', $orderIds)
                ->where('product_id', '!=', $product->id)
                ->select('product_id', DB::raw('COUNT(*) as occurrences'))
                ->groupBy('product_id')
                ->orderByDesc('occurrences')
                ->limit($limit)
                ->pluck('product_id');

            if ($coOccurringIds->isEmpty()) {
                return new Collection;
            }

            return Product::query()
                ->whereIn('id', $coOccurringIds)
                ->where('status', 'published')
                ->where('is_variation', false)
                ->with(['categories', 'brand', 'reviews'])
                ->get();
        });
    }

    /**
     * Productos populares como fallback (más vendidos en últimos 30 días).
     */
    public function popularProducts(int $limit = 8): Collection
    {
        return Cache::remember("ecommerce_popular_products_{$limit}", 1800, function () use ($limit) {
            $popularIds = DB::table('ecommerce_order_items')
                ->join('ecommerce_orders', 'ecommerce_orders.id', '=', 'ecommerce_order_items.order_id')
                ->where('ecommerce_orders.created_at', '>=', now()->subDays(30))
                ->select('ecommerce_order_items.product_id', DB::raw('SUM(ecommerce_order_items.qty) as total_sold'))
                ->groupBy('ecommerce_order_items.product_id')
                ->orderByDesc('total_sold')
                ->limit($limit)
                ->pluck('product_id');

            if ($popularIds->isEmpty()) {
                return Product::query()
                    ->where('status', 'published')
                    ->where('is_variation', false)
                    ->with(['categories', 'brand', 'reviews'])
                    ->latest()
                    ->limit($limit)
                    ->get();
            }

            return Product::query()
                ->whereIn('id', $popularIds)
                ->where('status', 'published')
                ->where('is_variation', false)
                ->with(['categories', 'brand', 'reviews'])
                ->get();
        });
    }
}
