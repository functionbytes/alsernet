<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariation;

class UpdateDefaultProductService
{
    public function execute(Product $product, int $defaultVariationId): void
    {
        ProductVariation::query()
            ->where('configurable_product_id', $product->id)
            ->update(['is_default' => false]);

        ProductVariation::query()
            ->where('id', $defaultVariationId)
            ->update(['is_default' => true]);
    }
}
