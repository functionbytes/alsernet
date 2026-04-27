<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Brand;

class BrandHelper
{
    public static function getActiveBrands(): Collection
    {
        return Cache::remember('ecommerce_active_brands', 3600, function () {
            return Brand::query()
                ->where('status', 'published')
                ->orderBy('name')
                ->get();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('ecommerce_active_brands');
    }
}
