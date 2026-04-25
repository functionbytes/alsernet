<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderPaymentConfirmed;

class OrderPaymentConfirmedNotification
{
    public function handle(OrderPaymentConfirmed $event): void
    {
        // TODO: Send payment confirmation email
    }
}
