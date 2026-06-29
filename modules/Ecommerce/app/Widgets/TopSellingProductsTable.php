<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\OrderItem;

class TopSellingProductsTable
{
    public function getProducts(int $limit = 10): array
    {
        return OrderItem::query()
            ->selectRaw('product_name, SUM(qty) as total_qty')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
