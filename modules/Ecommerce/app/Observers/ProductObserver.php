<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Supports\FeaturedProductHelper;

class ProductObserver
{
    public function saved(Product $product): void
    {
        if ($product->is_featured || $product->wasChanged('is_featured')) {
            FeaturedProductHelper::clearCache();
        }
    }

    public function deleted(Product $product): void
    {
        FeaturedProductHelper::clearCache();
    }
}
