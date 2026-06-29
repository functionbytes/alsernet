<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class StoreAttributesOfProductService
{
    public function execute(Product $product, array $attributeSetIds): void
    {
        $product->attributeSets()->sync($attributeSetIds);
    }
}
