<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Supports\DiscountSupport;

class DiscountService
{
    public function applyCoupon(string $code, float $subTotal): ?array
    {
        return DiscountSupport::apply($code, $subTotal);
    }

    public function getActiveDiscounts()
    {
        return Discount::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get();
    }
}
