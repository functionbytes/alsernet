<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\ShippingStatusChanged;

class SendShippingStatusChangedNotification
{
    public function handle(ShippingStatusChanged $event): void
    {
        // TODO: Send shipping status email to customer
    }
}
