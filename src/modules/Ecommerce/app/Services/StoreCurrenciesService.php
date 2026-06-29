<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Currency;

class StoreCurrenciesService
{
    public function execute(array $currencies): void
    {
        foreach ($currencies as $currencyData) {
            Currency::query()->updateOrCreate(
                ['title' => $currencyData['title']],
                [
                    'symbol' => $currencyData['symbol'],
                    'is_prefix_symbol' => $currencyData['is_prefix_symbol'] ?? false,
                    'decimals' => $currencyData['decimals'] ?? 2,
                    'exchange_rate' => $currencyData['exchange_rate'] ?? 1,
                    'is_default' => $currencyData['is_default'] ?? false,
                ]
            );
        }
    }
}
