<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class GetProductBySlugService
{
    public function execute(string $slug): ?Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with(['brand', 'categories', 'tags', 'variations', 'reviews'])
            ->first();
    }
}
