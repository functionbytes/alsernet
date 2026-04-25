<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Arr;
use Modules\Ecommerce\Models\Product;

class StoreProductService
{
    public function execute(array $data, ?Product $product = null): Product
    {
        $productData = Arr::except($data, ['categories', 'tags', 'attributes', 'variations', 'collections']);

        if ($product) {
            $product->update($productData);
        } else {
            $product = Product::query()->create($productData);
        }

        if (isset($data['categories'])) {
            $product->categories()->sync($data['categories']);
        }

        if (isset($data['tags'])) {
            $product->tags()->sync($data['tags']);
        }

        if (isset($data['collections'])) {
            $product->collections()->sync($data['collections']);
        }

        if (isset($data['attributes'])) {
            $product->attributeSets()->sync($data['attributes']);
        }

        return $product->load(['categories', 'tags', 'collections']);
    }
}
