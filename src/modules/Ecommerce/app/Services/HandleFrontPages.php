<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Supports\ProductCategoryHelper;

class HandleFrontPages
{
    public function getHomePageData(): array
    {
        return [
            'featured_products' => app(ProductService::class)->getFeaturedProducts(8),
            'categories' => app(ProductCategoryHelper::class)->getTree(),
            'flash_sales' => app(FlashSaleService::class)->getActiveFlashSales(),
        ];
    }
}
