<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\ShippingRule;

class DynamicShippingValidationService
{
    public function validate(float $subTotal, ?string $country = null, ?string $state = null): bool
    {
        return ShippingRule::query()
            ->where('type', 'based_on_price')
            ->where('from', '<=', $subTotal)
            ->where(function ($q) use ($subTotal) {
                $q->where('to', '>=', $subTotal)->orWhere('to', 0);
            })
            ->whereHas('items', function ($q) use ($country, $state) {
                $q->where('country', $country)
                    ->where('state', $state)
                    ->where('is_enabled', true);
            })
            ->exists();
    }
}
