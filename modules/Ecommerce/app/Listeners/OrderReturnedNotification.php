<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderReturned;

class OrderReturnedNotification
{
    public function handle(OrderReturned $event): void
    {
        // TODO: Send return notification
    }
}
