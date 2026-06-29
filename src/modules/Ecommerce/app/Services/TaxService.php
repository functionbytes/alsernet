<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;

class TaxService
{
    public function calculateTax(float $amount, ?string $country = null, ?string $state = null): float
    {
        $tax = Tax::query()
            ->where('status', 'published')
            ->first();

        if (! $tax) {
            return 0;
        }

        $rule = TaxRule::query()
            ->where('tax_id', $tax->id)
            ->where('price_from', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('price_to')->orWhere('price_to', '>=', $amount);
            })
            ->orderBy('order')
            ->first();

        $percentage = $rule ? $rule->percentage : $tax->percentage;

        return $amount * ($percentage / 100);
    }
}
