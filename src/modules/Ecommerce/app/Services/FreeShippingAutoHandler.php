<?php

namespace Modules\Ecommerce\Services;

class FreeShippingAutoHandler
{
    public function __construct(protected ShippingService $shippingService) {}

    public function execute(float $subTotal): ?float
    {
        $threshold = (float) setting('ecommerce_free_shipping_threshold', 0);

        if ($threshold > 0 && $subTotal >= $threshold) {
            return 0;
        }

        return null;
    }
}
