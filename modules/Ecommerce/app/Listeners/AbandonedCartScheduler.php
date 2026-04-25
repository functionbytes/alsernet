<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderPlaced;

class AbandonedCartScheduler
{
    public function handle(OrderPlaced $event): void
    {
        // Clear any scheduled abandoned cart emails for this customer
    }
}
