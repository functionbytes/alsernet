<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\Product;

class NewProductCard
{
    public function getCount(): int
    {
        return Product::query()->whereDate('created_at', today())->count();
    }
}
