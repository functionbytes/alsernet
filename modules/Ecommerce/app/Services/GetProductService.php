<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Product;

class GetProductService
{
    public function execute(int $id): ?Product
    {
        return Product::query()
            ->where('id', $id)
            ->with(['brand', 'categories', 'tags', 'variations', 'reviews'])
            ->first();
    }
}
