<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Models\FlashSale;

class FlashSaleService
{
    public function getActiveFlashSales(): Collection
    {
        return FlashSale::query()
            ->where('status', 'published')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('products')
            ->get();
    }

    public function isProductInFlashSale(int $productId): bool
    {
        return FlashSale::query()
            ->where('status', 'published')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->whereHas('products', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();
    }
}
