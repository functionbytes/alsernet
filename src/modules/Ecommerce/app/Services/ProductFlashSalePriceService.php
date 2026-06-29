<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class ProductFlashSalePriceService
{
    public function getPrice(Product $product): ?float
    {
        $flashSale = app(FlashSaleService::class)->getActiveFlashSales()
            ->flatMap->products
            ->firstWhere('product_id', $product->id);

        return $flashSale?->price;
    }
}
