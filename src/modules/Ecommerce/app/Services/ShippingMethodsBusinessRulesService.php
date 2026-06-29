<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Shipping;

class ShippingMethodsBusinessRulesService
{
    public function getAvailableMethods(float $subTotal, ?string $country = null): array
    {
        $methods = [];
        $shippings = Shipping::query()->with('rules.items')->get();

        foreach ($shippings as $shipping) {
            foreach ($shipping->rules as $rule) {
                if ($subTotal >= $rule->from && ($rule->to == 0 || $subTotal <= $rule->to)) {
                    $methods[] = [
                        'name' => $shipping->title,
                        'price' => $rule->price,
                    ];
                }
            }
        }

        return $methods;
    }
}
