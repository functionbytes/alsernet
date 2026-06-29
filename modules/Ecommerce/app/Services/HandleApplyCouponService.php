<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Discount;

class HandleApplyCouponService
{
    public function execute(string $couponCode, float $subTotal): array
    {
        $discount = Discount::query()
            ->where('code', $couponCode)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->first();

        if (! $discount) {
            return ['success' => false, 'message' => 'Cupón no válido'];
        }

        $amount = $discount->type_option === 'percentage'
            ? $subTotal * ($discount->value / 100)
            : $discount->value;

        return [
            'success' => true,
            'discount' => $discount,
            'amount' => min($amount, $subTotal),
        ];
    }
}
