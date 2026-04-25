<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\ShippingRule;

class HandleShippingFeeService
{
    public function execute(float $subTotal, ?string $country = null, ?string $state = null, ?string $city = null): float
    {
        $rule = ShippingRule::query()
            ->where('type', 'based_on_price')
            ->where('from', '<=', $subTotal)
            ->where(function ($q) use ($subTotal) {
                $q->where('to', '>=', $subTotal)->orWhere('to', 0);
            })
            ->first();

        if (! $rule) {
            return 0;
        }

        $price = (float) $rule->price;

        $item = $rule->items()
            ->where('country', $country)
            ->where('state', $state)
            ->where('city', $city)
            ->where('is_enabled', true)
            ->first();

        if ($item) {
            $price += (float) $item->adjustment_price;
        }

        return $price;
    }
}
