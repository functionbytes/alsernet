<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class DuplicateProductService
{
    public function execute(Product $product): Product
    {
        $newProduct = $product->replicate();
        $newProduct->name = $product->name.' (Copy)';
        $newProduct->slug = $product->slug.'-'.uniqid();
        $newProduct->sku = $product->sku ? $product->sku.'-'.uniqid() : null;
        $newProduct->save();

        $newProduct->categories()->sync($product->categories->pluck('id'));
        $newProduct->tags()->sync($product->tags->pluck('id'));
        $newProduct->collections()->sync($product->collections->pluck('id'));

        return $newProduct;
    }
}
