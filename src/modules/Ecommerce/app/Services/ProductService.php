<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Ecommerce\Models\Product;

class ProductService
{
    public function getProducts(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return Product::query()
            ->where('status', 'published')
            ->when($filters['category'] ?? null, function ($q, $category) {
                $q->whereHas('categories', fn ($q) => $q->where('slug', $category));
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when($filters['brand'] ?? null, function ($q, $brand) {
                $q->whereHas('brand', fn ($q) => $q->where('slug', $brand));
            })
            ->when($filters['min_price'] ?? null, function ($q, $price) {
                $q->where('price', '>=', $price);
            })
            ->when($filters['max_price'] ?? null, function ($q, $price) {
                $q->where('price', '<=', $price);
            })
            ->with(['brand', 'categories'])
            ->paginate($perPage);
    }

    public function getFeaturedProducts(int $limit = 8)
    {
        return Product::query()
            ->where('status', 'published')
            ->where('is_featured', true)
            ->with('brand')
            ->limit($limit)
            ->get();
    }

    public function getRelatedProducts(Product $product, int $limit = 4)
    {
        return Product::query()
            ->where('status', 'published')
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($q) use ($product) {
                $q->whereIn('ecommerce_product_categories.id', $product->categories->pluck('id'));
            })
            ->limit($limit)
            ->get();
    }
}
