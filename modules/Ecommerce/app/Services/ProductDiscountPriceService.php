<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Product;

class ProductDiscountPriceService
{
    public function getDiscountPrice(Product $product, ?Discount $discount = null): ?float
    {
        if (! $discount) {
            return null;
        }

        $price = app(ProductPriceService::class)->getPrice($product);

        if ($discount->type_option === 'percentage') {
            return $price - ($price * ($discount->value / 100));
        }

        if ($discount->type_option === 'amount') {
            return max(0, $price - $discount->value);
        }

        return $price;
    }
}
