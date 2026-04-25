<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariation;
use Modules\Ecommerce\Models\ProductVariationItem;

class CreateProductVariationsService
{
    public function execute(Product $product, array $variations): void
    {
        foreach ($variations as $variationData) {
            $variation = ProductVariation::query()->create([
                'product_id' => $variationData['product_id'] ?? null,
                'configurable_product_id' => $product->id,
                'is_default' => $variationData['is_default'] ?? false,
            ]);

            foreach ($variationData['attributes'] ?? [] as $attributeId) {
                ProductVariationItem::query()->create([
                    'variation_id' => $variation->id,
                    'attribute_id' => $attributeId,
                ]);
            }
        }
    }
}
