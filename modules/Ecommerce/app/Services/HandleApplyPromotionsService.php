<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Discount;

class HandleApplyPromotionsService
{
    public function execute(float $subTotal): array
    {
        $discounts = Discount::query()
            ->where('status', 'published')
            ->where('type', 'promotion')
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->get();

        $totalDiscount = 0;
        foreach ($discounts as $discount) {
            $amount = $discount->type_option === 'percentage'
                ? $subTotal * ($discount->value / 100)
                : $discount->value;

            $totalDiscount += min($amount, $subTotal);
        }

        return [
            'discounts' => $discounts,
            'amount' => min($totalDiscount, $subTotal),
        ];
    }
}
