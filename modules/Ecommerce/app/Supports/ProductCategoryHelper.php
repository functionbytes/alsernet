<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\ProductCategory;

class ProductCategoryHelper
{
    public static function getTree(): array
    {
        return Cache::remember('ecommerce_categories_tree', 3600, function () {
            return ProductCategory::query()
                ->where('status', 'published')
                ->where('parent_id', 0)
                ->with('children')
                ->get()
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('ecommerce_categories_tree');
    }
}
