<?php

namespace Modules\Ecommerce\Widgets;

use Modules\Ecommerce\Models\Order;

class NewOrderCard
{
    public function getCount(): int
    {
        return Order::query()->whereDate('created_at', today())->count();
    }
}
