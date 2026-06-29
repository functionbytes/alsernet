<?php

namespace Modules\Ecommerce\Services;

class HandleSetCountryForPaymentCheckout
{
    public function execute(array $data): array
    {
        $country = $data['country'] ?? null;
        $state = $data['state'] ?? null;

        return [
            'country' => $country,
            'state' => $state,
            'tax_rate' => app(TaxCalculatorService::class)->calculate(100, $country, $state)['rate'] ?? 0,
        ];
    }
}
