<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Cart\ApplyCouponRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\CartResource;
use Modules\Ecommerce\Models\Cart;

class CartController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $items = Cart::query()
            ->where('customer_id', auth()->id())
            ->with('product.brand')
            ->get();

        return $this->ok((new CartResource($items))->toArray(request()));
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $customerId = $request->user()->id;
        $productId = (int) $request->input('product_id');
        $qty = (int) $request->input('qty', 1);
        $options = $request->input('options', []);

        $existing = Cart::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->increment('qty', $qty);
        } else {
            Cart::query()->create([
                'customer_id' => $customerId,
                'product_id' => $productId,
                'qty' => $qty,
                'options' => $options,
            ]);
        }

        return $this->index();
    }

    public function update(UpdateCartItemRequest $request, Cart $cart): JsonResponse
    {
        if ($cart->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $cart->update(['qty' => (int) $request->input('qty')]);

        return $this->index();
    }

    public function destroy(Cart $cart): JsonResponse
    {
        if ($cart->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $cart->delete();

        return $this->index();
    }

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $code = $request->input('code');

        return $this->ok([
            'couponCode' => $code,
            'message' => 'Cupón aplicado.',
        ]);
    }

    public function removeCoupon(): JsonResponse
    {
        return $this->ok([
            'couponCode' => null,
            'message' => 'Cupón removido.',
        ]);
    }
}
