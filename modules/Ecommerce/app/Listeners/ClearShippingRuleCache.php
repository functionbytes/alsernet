<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Support\Facades\Cache;

class ClearShippingRuleCache
{
    public function handle(): void
    {
        Cache::forget('ecommerce_shipping_rules');
    }
}
