<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\Order;

class OrderChart
{
    public function getData(): array
    {
        return Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
