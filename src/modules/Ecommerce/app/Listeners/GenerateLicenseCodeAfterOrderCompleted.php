<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Ecommerce\Events\OrderCompleted;

class GenerateLicenseCodeAfterOrderCompleted
{
    public function handle(OrderCompleted $event): void
    {
        foreach ($event->order->items as $item) {
            $product = $item->product;
            if ($product && $product->generate_license_code) {
                // Generate license code logic
            }
        }
    }
}
