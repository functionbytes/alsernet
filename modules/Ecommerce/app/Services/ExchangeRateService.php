<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Currency;

class ExchangeRateService
{
    public function getRate(string $from, string $to): float
    {
        $key = "ecommerce_exchange_rate_{$from}_{$to}";

        return Cache::remember($key, 3600, function () use ($from, $to) {
            $fromCurrency = Currency::query()->where('title', $from)->orWhere('symbol', $from)->first();
            $toCurrency = Currency::query()->where('title', $to)->orWhere('symbol', $to)->first();

            if (! $fromCurrency || ! $toCurrency || $fromCurrency->exchange_rate == 0) {
                return 1;
            }

            return $toCurrency->exchange_rate / $fromCurrency->exchange_rate;
        });
    }

    public function convert(float $amount, string $from, string $to): float
    {
        return $amount * $this->getRate($from, $to);
    }
}
