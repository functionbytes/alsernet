<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Cart\ApplyCouponRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\CartResource;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Services\DiscountService;

/**
 * @group Carrito
 *
 * Gestión del carrito de compras del cliente autenticado.
 */
class CartController extends BaseApiController
{
    public function __construct(
        protected DiscountService $discountService,
    ) {}

    /**
     * Ver carrito
     *
     * Devuelve los ítems del carrito del cliente con totales calculados.
     */
    public function index(): JsonResponse
    {
        $items = Cart::query()
            ->where('customer_id', auth()->id())
            ->with('product.brand')
            ->get();

        return $this->ok((new CartResource($items))->toArray(request()));
    }

    /**
     * Agregar ítem al carrito
     *
     * Agrega un producto al carrito. Si ya existe, incrementa la cantidad.
     */
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

    /**
     * Actualizar cantidad de ítem
     *
     * Actualiza la cantidad de un ítem del carrito. Enviar qty=0 para eliminarlo.
     */
    public function update(UpdateCartItemRequest $request, Cart $cart): JsonResponse
    {
        if ($cart->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $cart->update(['qty' => (int) $request->input('qty')]);

        return $this->index();
    }

    /** Eliminar ítem del carrito */
    public function destroy(Cart $cart): JsonResponse
    {
        if ($cart->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $cart->delete();

        return $this->index();
    }

    /**
     * Aplicar cupón de descuento
     *
     * Valida y aplica un código de cupón al carrito activo.
     */
    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $items = Cart::query()
            ->where('customer_id', auth()->id())
            ->with('product')
            ->get();

        $subTotal = $items->sum(fn ($i) => (float) ($i->product->price ?? 0) * (int) $i->qty);

        $result = $this->discountService->applyCoupon($request->input('code'), $subTotal);

        if (! $result['success']) {
            return $this->errorResponse($result['message'], $result['code'] ?? 'COUPON_INVALID', 422);
        }

        $discount = $result['discount'];
        $discountAmount = round($result['amount'], 2);
        $newSubTotal = max(0.0, $subTotal - $discountAmount);

        return $this->ok([
            'couponCode' => $discount->code,
            'discountType' => $discount->type->value,
            'discountAmount' => $discountAmount,
            'freeShipping' => $result['free_shipping'],
            'newSubTotal' => round($newSubTotal, 2),
        ]);
    }

    /** Eliminar cupón aplicado */
    public function removeCoupon(): JsonResponse
    {
        return $this->ok(['couponCode' => null]);
    }
}
