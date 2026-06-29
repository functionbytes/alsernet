<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\Order;

class RevenueCard
{
    public function getTotalRevenue(): float
    {
        return (float) Order::query()
            ->where('status', 'completed')
            ->sum('total');
    }

    public function getTodayRevenue(): float
    {
        return (float) Order::query()
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('total');
    }
}
