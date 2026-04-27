<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\Discount;

class CouponApiController extends Controller
{
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        $discount = Discount::query()
            ->where('code', $request->input('code'))
            ->where('is_active', true)
            ->first();

        if (! $discount || $discount->isExpired()) {
            return response()->json([
                'message' => 'El código de descuento no es válido o ha expirado.',
            ], 422);
        }

        $orderTotal = (float) $request->input('order_total');

        if ($discount->min_order_price && $orderTotal < $discount->min_order_price) {
            return response()->json([
                'message' => "El pedido mínimo para este cupón es de {$discount->min_order_price}.",
            ], 422);
        }

        $isFreeShipping = $discount->type === DiscountType::FREE_SHIPPING;
        $discountAmount = $discount->calculateDiscount($orderTotal);

        return response()->json([
            'success' => true,
            'code' => $discount->code,
            'discountType' => $discount->type->value,
            'discountAmount' => round($discountAmount, 2),
            'freeShipping' => $isFreeShipping,
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        return response()->json(['success' => true]);
    }
}
