<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;

class HandleTaxService
{
    public function execute(float $amount, ?string $country = null, ?string $state = null): float
    {
        $tax = Tax::query()->where('status', 'published')->first();

        if (! $tax) {
            return 0;
        }

        $rule = TaxRule::query()
            ->where('tax_id', $tax->id)
            ->where(function ($q) use ($country) {
                $q->whereNull('country')->orWhere('country', $country);
            })
            ->where(function ($q) use ($state) {
                $q->whereNull('state')->orWhere('state', $state);
            })
            ->first();

        $percentage = $rule ? $rule->percentage : $tax->percentage;

        return $amount * ($percentage / 100);
    }
}
