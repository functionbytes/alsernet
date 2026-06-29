<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Http\Requests\Api\V1\Checkout\CreateOrderRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\OrderDetailResource;
use Modules\Ecommerce\Http\Resources\Api\V1\OrderResource;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CustomerAddress;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\CheckoutService;

/**
 * @group Pedidos
 *
 * Creación, consulta y cancelación de pedidos del cliente autenticado.
 */
class OrderController extends BaseApiController
{
    /**
     * Listar pedidos
     *
     * Devuelve los pedidos del cliente paginados, opcionalmente filtrados por estado.
     *
     * @queryParam filter[status] string Filtrar por estado. Valores: pending,processing,completed,cancelled. Example: completed
     * @queryParam per_page integer Items por página. Example: 15
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('customer_id', auth()->id())
            ->withCount('items')
            ->when(
                $request->input('filter.status'),
                fn ($q, $status) => $q->where('status', $status)
            )
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated($orders, OrderResource::class);
    }

    /**
     * Detalle de pedido
     *
     * Devuelve el detalle completo del pedido incluyendo ítems y direcciones.
     *
     * @urlParam order integer required ID del pedido. Example: 42
     */
    public function show(Order $order): JsonResponse
    {
        if ($order->customer_id !== auth()->id()) {
            return $this->errorResponse('Pedido no encontrado.', 'NOT_FOUND', 404);
        }

        $order->load(['items', 'addresses']);

        return $this->ok(new OrderDetailResource($order));
    }

    /**
     * Crear pedido
     *
     * Procesa el carrito actual y crea un pedido. El carrito queda vacío al completar.
     * Usar la cabecera `Idempotency-Key` para evitar pedidos duplicados por reintentos de red.
     *
     * @header Idempotency-Key string UUID único para idempotencia. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function store(CreateOrderRequest $request, CheckoutService $checkout): JsonResponse
    {
        $customer = $request->user();

        // Set customer on the ecommerce guard so CartService::getCartItems()
        // and CheckoutService dependencies pick the right user.
        Auth::guard('ecommerce')->setUser($customer);

        $items = Cart::query()
            ->where('customer_id', $customer->id)
            ->with('product')
            ->get();

        if ($items->isEmpty()) {
            return $this->errorResponse('El carrito está vacío.', 'CART_EMPTY', 422);
        }

        $shippingAddress = CustomerAddress::query()->findOrFail($request->input('shipping_address_id'));
        if ($shippingAddress->customer_id !== $customer->id) {
            return $this->errorResponse('Dirección no autorizada.', 'FORBIDDEN', 403);
        }

        $order = DB::transaction(function () use ($checkout, $request, $customer, $shippingAddress) {
            $order = $checkout->process([
                'customer_id' => $customer->id,
                'country' => $shippingAddress->country,
                'state' => $shippingAddress->state,
                'shipping_method' => $request->input('shipping_method'),
                'payment_method' => $request->input('payment_method'),
                'coupon_code' => $request->input('coupon_code'),
            ]);

            $order->forceFill([
                'customer_note' => $request->input('customer_note'),
            ])->save();

            // Persist the shipping address snapshot on the order
            $order->addresses()->create([
                'type' => 'shipping',
                'name' => $shippingAddress->name,
                'phone' => $shippingAddress->phone,
                'email' => $customer->email,
                'country' => $shippingAddress->country,
                'state' => $shippingAddress->state,
                'city' => $shippingAddress->city,
                'address' => $shippingAddress->address,
            ]);

            if ($request->filled('billing_address_id')) {
                $billing = CustomerAddress::query()->find($request->input('billing_address_id'));

                if ($billing && $billing->customer_id === $customer->id) {
                    $order->addresses()->create([
                        'type' => 'billing',
                        'name' => $billing->name,
                        'phone' => $billing->phone,
                        'email' => $customer->email,
                        'country' => $billing->country,
                        'state' => $billing->state,
                        'city' => $billing->city,
                        'address' => $billing->address,
                    ]);
                }
            }

            return $order;
        });

        $order->load(['items', 'addresses']);

        return $this->created(new OrderDetailResource($order));
    }

    /**
     * Cancelar pedido
     *
     * Cancela un pedido que aún esté en estado `pending` o `processing`.
     *
     * @urlParam order integer required ID del pedido. Example: 42
     */
    public function cancel(Order $order): JsonResponse
    {
        if ($order->customer_id !== auth()->id()) {
            return $this->errorResponse('Pedido no encontrado.', 'NOT_FOUND', 404);
        }

        if (! in_array($order->status, [OrderStatus::PENDING, OrderStatus::PROCESSING], true)) {
            return $this->errorResponse('El pedido ya no puede cancelarse.', 'CANNOT_CANCEL', 422);
        }

        $order->forceFill(['status' => OrderStatus::CANCELLED])->save();

        OrderCancelled::dispatch($order);

        return $this->ok(new OrderResource($order->fresh()));
    }
}
