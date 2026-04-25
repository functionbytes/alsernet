<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;

class TaxCalculatorService
{
    public function calculate(float $amount, ?string $country = null, ?string $state = null, ?string $zipCode = null): array
    {
        $tax = Tax::query()->where('status', 'published')->first();

        if (! $tax) {
            return ['amount' => 0, 'rate' => 0];
        }

        $rule = TaxRule::query()
            ->where('tax_id', $tax->id)
            ->where(function ($q) use ($country) {
                $q->whereNull('country')->orWhere('country', $country);
            })
            ->where(function ($q) use ($state) {
                $q->whereNull('state')->orWhere('state', $state);
            })
            ->where(function ($q) use ($zipCode) {
                $q->whereNull('zip_code')->orWhere('zip_code', $zipCode);
            })
            ->first();

        $percentage = $rule ? $rule->percentage : $tax->percentage;
        $taxAmount = $amount * ($percentage / 100);

        return [
            'amount' => round($taxAmount, 2),
            'rate' => $percentage,
        ];
    }
}
