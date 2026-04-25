<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderPlaced;
use Modules\Ecommerce\Supports\InvoiceHelper;

class GenerateInvoiceListener
{
    public function handle(OrderPlaced $event): void
    {
        InvoiceHelper::store($event->order);
    }
}
