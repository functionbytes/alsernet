<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderCreated;

class OrderCreatedNotification
{
    public function handle(OrderCreated $event): void
    {
        // TODO: Send email to customer and admin
    }
}
