<?php

namespace Modules\Ecommerce\Observers;

use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Supports\ProductCategoryHelper;

class ProductCategoryObserver
{
    public function saved(ProductCategory $category): void
    {
        ProductCategoryHelper::clearCache();
    }

    public function deleted(ProductCategory $category): void
    {
        ProductCategoryHelper::clearCache();
    }
}
