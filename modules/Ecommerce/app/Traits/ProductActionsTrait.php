<?php

namespace Modules\Ecommerce\Traits;

use Modules\Ecommerce\Models\Product;

trait ProductActionsTrait
{
    public function duplicateProduct(Product $product): Product
    {
        $newProduct = $product->replicate();
        $newProduct->name = $product->name.' (Copy)';
        $newProduct->slug = $product->slug.'-'.uniqid();
        $newProduct->sku = $product->sku.'-'.uniqid();
        $newProduct->save();

        return $newProduct;
    }
}
