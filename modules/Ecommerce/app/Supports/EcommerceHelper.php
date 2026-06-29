<?php

namespace Modules\Ecommerce\Supports;

use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Currency;

class EcommerceHelper
{
    public static function getCurrency(): Currency
    {
        return Cache::remember('ecommerce_default_currency', 3600, function () {
            return Currency::query()->where('is_default', true)->first() ?? Currency::query()->first();
        });
    }

    public static function formatPrice(float $amount, ?Currency $currency = null): string
    {
        $currency ??= self::getCurrency();
        $symbol = $currency->symbol ?? '$';

        return $currency->is_prefix_symbol
            ? $symbol.number_format($amount, (int) $currency->decimals)
            : number_format($amount, (int) $currency->decimals).$symbol;
    }

    public static function isDisplayProductIncludingTaxes(): bool
    {
        return (bool) setting('ecommerce_display_product_price_including_taxes', false);
    }

    public static function getMinimumOrderAmount(): float
    {
        return (float) setting('ecommerce_minimum_order_amount', 0);
    }

    public static function getProductsPerPage(): int
    {
        return (int) setting('ecommerce_products_per_page', 12);
    }
}
