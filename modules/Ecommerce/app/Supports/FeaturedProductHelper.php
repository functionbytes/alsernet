<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Product;

class FeaturedProductHelper
{
    public static function getFeatured(int $limit = 12): Collection
    {
        return Cache::remember("ecommerce_featured_products_{$limit}", 600, function () use ($limit) {
            return Product::query()
                ->where('status', 'published')
                ->where('is_featured', true)
                ->with(['categories', 'reviews'])
                ->latest()
                ->limit($limit)
                ->get();
        });
    }

    public static function clearCache(): void
    {
        for ($i = 4; $i <= 24; $i++) {
            Cache::forget("ecommerce_featured_products_{$i}");
        }
    }
}
