<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderCancelled;

class OrderCancelledNotification
{
    public function handle(OrderCancelled $event): void
    {
        // TODO: Send cancellation email
    }
}
