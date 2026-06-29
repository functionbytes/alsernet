<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class GetProductWithCrossSalesBySlugService
{
    public function execute(string $slug): ?Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'brand',
                'categories',
                'tags',
                'variations',
                'reviews',
                'crossSales' => function ($q) {
                    $q->where('status', 'published');
                },
            ])
            ->first();
    }
}
