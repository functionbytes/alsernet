<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryHelper
{
    public static function getTree(): array
    {
        $query = fn () => ProductCategory::query()
            ->where('status', 'published')
            ->where('parent_id', 0)
            ->with('children')
            ->get()
            ->toArray();

        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            return Cache::tags(['ecommerce', 'categories'])->remember('ecommerce_categories_tree', 3600, $query);
        }

        return Cache::remember('ecommerce_categories_tree', 3600, $query);
    }

    public static function getNavigationCategories(): Collection
    {
        $query = fn () => ProductCategory::query()
            ->where('parent_id', 0)
            ->where('status', 'published')
            ->orderBy('order')
            ->with('children')
            ->get();

        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            return Cache::tags(['ecommerce', 'categories'])->remember('navigation_categories', 3600, $query);
        }

        return Cache::remember('ecommerce_navigation_categories', 3600, $query);
    }

    public static function clearCache(): void
    {
        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            Cache::tags(['ecommerce', 'categories'])->flush();
        }

        Cache::forget('ecommerce_categories_tree');
        Cache::forget('ecommerce_navigation_categories');
    }
}
