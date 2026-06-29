<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\ApplyCartDiscountRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\GenerateOrderRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\StoreAssistedCartItemRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\UpdateAssistedCartItemRequest;
use Modules\HelpdeskPrestashop\Models\AssistedCart;
use Modules\HelpdeskPrestashop\Models\AssistedCartItem;
use Modules\HelpdeskPrestashop\Services\AssistedCartService;
use RuntimeException;

class AssistedCartController extends Controller
{
    public function __construct(
        private readonly AssistedCartService $service,
    ) {}

    /**
     * Current building cart for the customer (creates one if none exists).
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $cart = $this->service->getOrCreateCart(
            $customer,
            $request->integer('conversation_id') ?: null,
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'cart' => $this->service->summarize($cart),
        ]);
    }

    public function addItem(StoreAssistedCartItemRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $cart = $this->service->getOrCreateCart(
            $customer,
            $request->integer('conversation_id') ?: null,
            $request->user()->id,
        );

        try {
            $this->service->addItem($cart, $request->integer('product_id'), $request->integer('quantity') ?: 1);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => true,
            'cart' => $this->service->summarize($cart->refresh()),
        ]);
    }

    public function updateItem(UpdateAssistedCartItemRequest $request, Customer $customer, AssistedCartItem $item): JsonResponse
    {
        $this->authorize('update', $customer);
        $this->assertItemBelongsToCustomer($item, $customer);

        try {
            $this->service->updateQuantity($item, $request->integer('quantity'));
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => true,
            'cart' => $this->service->summarize($item->cart->refresh()),
        ]);
    }

    public function removeItem(Customer $customer, AssistedCartItem $item): JsonResponse
    {
        $this->authorize('update', $customer);
        $this->assertItemBelongsToCustomer($item, $customer);

        $cart = $item->cart;

        try {
            $this->service->removeItem($item);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => true,
            'cart' => $this->service->summarize($cart->refresh()),
        ]);
    }

    public function applyDiscount(ApplyCartDiscountRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $cart = $this->service->getOrCreateCart($customer, null, $request->user()->id);

        try {
            if ($request->filled('shipping_amount')) {
                $this->service->setShipping($cart, (float) $request->input('shipping_amount'));
            }
            $result = $this->service->applyDiscount($cart, $request->input('code'));
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => $this->service->summarize($cart->refresh()),
        ], $result['success'] ? 200 : 422);
    }

    public function clear(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $cart = $this->service->getOrCreateCart($customer);

        try {
            $this->service->clear($cart);
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => true,
            'cart' => $this->service->summarize($cart->refresh()),
        ]);
    }

    public function generateOrder(GenerateOrderRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $cart = $this->service->getOrCreateCart($customer);

        try {
            $order = $this->service->generateOrder($cart, $request->validated());
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido '.$order->code.' generado correctamente.',
            'order' => [
                'id' => $order->id,
                'code' => $order->code,
                'total' => (float) $order->total,
            ],
            'cart' => $this->service->summarize($cart->refresh()),
        ]);
    }

    public function sendPaymentLink(GenerateOrderRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $cart = $this->service->getOrCreateCart($customer);

        try {
            $url = $this->service->sendPaymentLink($cart, $request->validated());
        } catch (RuntimeException $e) {
            return $this->businessError($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link de pago enviado al cliente.',
            'payment_url' => $url,
            'cart' => $this->service->summarize($cart->refresh()),
        ]);
    }

    /**
     * List all assisted carts for the customer.
     */
    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $carts = AssistedCart::query()
            ->forCustomer($customer->id)
            ->withCount('items')
            ->with('items')
            ->latest()
            ->limit(50)
            ->get();

        $groups = ['active' => 0, 'abandoned' => 0, 'converted' => 0];
        foreach ($carts as $cart) {
            $groups[$cart->status_group] = ($groups[$cart->status_group] ?? 0) + 1;
        }

        return response()->json([
            'success' => true,
            'counts' => [
                'all' => $carts->count(),
                'active' => $groups['active'],
                'abandoned' => $groups['abandoned'],
                'converted' => $groups['converted'],
            ],
            'carts' => $carts->map(fn (AssistedCart $cart) => [
                'id' => $cart->id,
                'reference' => 'CART-#'.$cart->id,
                'status' => $cart->status,
                'status_label' => $cart->status_label,
                'status_group' => $cart->status_group,
                'items_count' => $cart->items_count,
                'units_count' => $cart->units_count,
                'total' => (float) $cart->total,
                'currency' => $cart->currency,
                'order_code' => $cart->order_code,
                'created_at' => $cart->created_at?->toIso8601String(),
                'created_at_human' => $cart->created_at?->diffForHumans(),
                'updated_at_human' => $cart->updated_at?->diffForHumans(),
            ])->all(),
        ]);
    }

    /**
     * Detail of a single assisted cart, with an event timeline.
     */
    public function showCart(Customer $customer, AssistedCart $cart): JsonResponse
    {
        $this->authorize('view', $customer);
        $this->assertCartBelongsToCustomer($cart, $customer);

        $timeline = [];
        $timeline[] = ['icon' => 'fa-plus', 'title' => 'Carrito creado', 'at' => $cart->created_at?->toIso8601String(), 'at_human' => $cart->created_at?->diffForHumans()];
        if ($cart->sent_at) {
            $timeline[] = ['icon' => 'fa-paper-plane', 'title' => 'Link de pago enviado', 'at' => $cart->sent_at->toIso8601String(), 'at_human' => $cart->sent_at->diffForHumans()];
        }
        if ($cart->converted_at) {
            $timeline[] = ['icon' => 'fa-receipt', 'title' => 'Convertido a pedido '.$cart->order_code, 'at' => $cart->converted_at->toIso8601String(), 'at_human' => $cart->converted_at->diffForHumans()];
        }

        return response()->json([
            'success' => true,
            'cart' => array_merge($this->service->summarize($cart), [
                'reference' => 'CART-#'.$cart->id,
                'timeline' => $timeline,
            ]),
        ]);
    }

    private function assertItemBelongsToCustomer(AssistedCartItem $item, Customer $customer): void
    {
        abort_unless($item->cart && $item->cart->customer_id === $customer->id, 404);
    }

    private function assertCartBelongsToCustomer(AssistedCart $cart, Customer $customer): void
    {
        abort_unless($cart->customer_id === $customer->id, 404);
    }

    private function businessError(RuntimeException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}
