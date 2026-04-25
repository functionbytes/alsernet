<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Currency;

class CurrencySupport
{
    public static function getDefaultCurrency(): Currency
    {
        return Cache::remember('ecommerce_default_currency', 3600, function () {
            return Currency::query()->where('is_default', true)->first() ?? new Currency([
                'title' => 'US Dollar',
                'symbol' => '$',
                'is_prefix_symbol' => true,
                'decimals' => 2,
                'exchange_rate' => 1,
            ]);
        });
    }

    public static function convert(float $amount, ?float $rate = null): float
    {
        $rate ??= self::getDefaultCurrency()->exchange_rate ?? 1;

        return $amount * $rate;
    }
}
