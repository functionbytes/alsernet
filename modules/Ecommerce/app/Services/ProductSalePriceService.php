<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class ProductSalePriceService
{
    public function getSalePrice(Product $product): ?float
    {
        if (! $product->sale_price || $product->sale_price <= 0) {
            return null;
        }

        if ($product->start_date && $product->end_date) {
            if (! now()->between($product->start_date, $product->end_date)) {
                return null;
            }
        }

        return (float) $product->sale_price;
    }
}
