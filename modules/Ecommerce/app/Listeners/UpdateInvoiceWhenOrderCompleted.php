<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderCompleted;
use Modules\Ecommerce\Models\Invoice;

class UpdateInvoiceWhenOrderCompleted
{
    public function handle(OrderCompleted $event): void
    {
        Invoice::query()
            ->where('reference_type', get_class($event->order))
            ->where('reference_id', $event->order->id)
            ->update(['status' => 'paid']);
    }
}
