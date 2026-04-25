<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class ProductCrossSalePriceService
{
    public function getPrice(Product $product): ?float
    {
        $relation = $product->crossSales()->first();

        if (! $relation) {
            return null;
        }

        return $relation->pivot->price ?? null;
    }
}
