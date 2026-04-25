<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\Order;

class OrderCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Order $order) {}
}
