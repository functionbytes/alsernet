<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\FlashSale;

class FlashSaleService
{
    public function getActiveFlashSales(): Collection
    {
        return Cache::remember('ecommerce_active_flash_sales', 300, function () {
            return FlashSale::query()
                ->where('status', 'published')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->with('products')
                ->get();
        });
    }

    public function isProductInFlashSale(int $productId): bool
    {
        return $this->getActiveFlashSales()
            ->flatMap(fn ($sale) => $sale->products)
            ->contains('id', $productId);
    }

    public static function clearCache(): void
    {
        Cache::forget('ecommerce_active_flash_sales');
    }
}
