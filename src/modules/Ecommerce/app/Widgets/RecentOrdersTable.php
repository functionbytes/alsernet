<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\Order;

class RecentOrdersTable
{
    public function getOrders(int $limit = 10)
    {
        return Order::query()
            ->with('customer')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
