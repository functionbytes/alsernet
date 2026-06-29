<?php

namespace Modules\Ecommerce\Supports;

use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\Discount;

class DiscountSupport
{
    /**
     * Validate and calculate a coupon discount.
     *
     * @return array{success: bool, discount?: Discount, amount: float, message?: string, free_shipping: bool}
     */
    public function apply(string $code, float $subTotal): array
    {
        $discount = Discount::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $discount) {
            return [
                'success' => false,
                'amount' => 0.0,
                'message' => 'El código de descuento no es válido.',
                'code' => 'COUPON_NOT_FOUND',
                'free_shipping' => false,
            ];
        }

        if ($discount->isExpired()) {
            return [
                'success' => false,
                'amount' => 0.0,
                'message' => 'El código de descuento ha expirado o alcanzó su límite de usos.',
                'code' => 'COUPON_EXPIRED',
                'free_shipping' => false,
            ];
        }

        if ($discount->min_order_price && $subTotal < (float) $discount->min_order_price) {
            return [
                'success' => false,
                'amount' => 0.0,
                'message' => "El pedido mínimo para este cupón es de {$discount->min_order_price}.",
                'code' => 'COUPON_MIN_ORDER',
                'free_shipping' => false,
            ];
        }

        $amount = $discount->calculateDiscount($subTotal);

        return [
            'success' => true,
            'discount' => $discount,
            'amount' => $amount,
            'free_shipping' => $discount->type === DiscountType::FREE_SHIPPING,
        ];
    }
}
