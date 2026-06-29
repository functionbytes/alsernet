<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Shipping;

class ShippingService
{
    public function calculateShipping(float $subTotal, ?string $country = null, ?string $state = null): float
    {
        $shipping = Shipping::query()
            ->with('rules.items')
            ->first();

        if (! $shipping) {
            return 0;
        }

        $rule = $shipping->rules()
            ->where('type', 'based_on_price')
            ->where('from', '<=', $subTotal)
            ->where(function ($q) use ($subTotal) {
                $q->where('to', '>=', $subTotal)->orWhere('to', 0);
            })
            ->first();

        if (! $rule) {
            return 0;
        }

        $price = $rule->price;

        if ($country && $state) {
            $item = $rule->items()
                ->where('country', $country)
                ->where('state', $state)
                ->where('is_enabled', true)
                ->first();

            if ($item) {
                $price += $item->adjustment_price;
            }
        }

        return (float) $price;
    }
}
