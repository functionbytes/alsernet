<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderPaymentConfirmed;
use Modules\Ecommerce\Models\Invoice;

class UpdateInvoiceWhenPaymentConfirmed
{
    public function handle(OrderPaymentConfirmed $event): void
    {
        Invoice::query()
            ->where('reference_type', get_class($event->order))
            ->where('reference_id', $event->order->id)
            ->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
