<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Models\Invoice;
use Modules\Ecommerce\Models\Shipment;

class UpdateInvoiceAndShippingWhenOrderCancelled
{
    public function handle(OrderCancelled $event): void
    {
        Invoice::query()
            ->where('reference_type', get_class($event->order))
            ->where('reference_id', $event->order->id)
            ->update(['status' => 'cancelled']);

        Shipment::query()
            ->where('order_id', $event->order->id)
            ->update(['status' => 'cancelled']);
    }
}
