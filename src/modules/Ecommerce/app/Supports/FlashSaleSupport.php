<?php

namespace Modules\Ecommerce\Supports;

use Modules\Ecommerce\Models\FlashSale;
use Modules\Ecommerce\Models\Product;

class FlashSaleSupport
{
    public static function getPriceForProduct(Product $product): ?float
    {
        $flashSale = FlashSale::query()
            ->where('status', 'published')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->whereHas('products', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->first();

        if (! $flashSale) {
            return null;
        }

        return $flashSale->products()
            ->where('product_id', $product->id)
            ->value('price');
    }
}
