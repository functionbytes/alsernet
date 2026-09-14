<?php

namespace Modules\HelpdeskPrestashop\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Models\Customer as EcommerceCustomer;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderAddress;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Services\DiscountService;
use Modules\Ecommerce\Services\OrderService;
use Modules\Helpdesk\Jobs\SendHelpdeskEmailJob;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Models\AssistedCart;
use Modules\HelpdeskPrestashop\Models\AssistedCartItem;
use RuntimeException;

class AssistedCartService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly DiscountService $discountService,
    ) {}

    /**
     * Get the active (building) cart for a customer, or create a new one.
     */
    public function getOrCreateCart(Customer $customer, ?int $conversationId = null, ?int $userId = null): AssistedCart
    {
        $cart = AssistedCart::query()
            ->forCustomer($customer->id)
            ->building()
            ->latest()
            ->first();

        if ($cart) {
            return $cart->load('items');
        }

        $cart = AssistedCart::query()->create([
            'customer_id' => $customer->id,
            'conversation_id' => $conversationId,
            'created_by_user_id' => $userId,
            'status' => AssistedCart::STATUS_BUILDING,
            'currency' => 'EUR',
        ]);

        return $cart->load('items');
    }

    /**
     * Add a product to the cart (or increment quantity if already present).
     */
    public function addItem(AssistedCart $cart, int $productId, int $quantity = 1): AssistedCartItem
    {
        $this->assertEditable($cart);

        $product = Product::query()->findOrFail($productId);

        $existing = $cart->items()->where('product_id', $productId)->first();

        if ($existing) {
            $existing->increment('quantity', max(1, $quantity));

            return $existing->refresh();
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'image_url' => $product->featured_image,
            'unit_price' => $product->final_price,
            'quantity' => max(1, $quantity),
        ]);
    }

    public function updateQuantity(AssistedCartItem $item, int $quantity): AssistedCartItem
    {
        $this->assertEditable($item->cart);

        if ($quantity <= 0) {
            $item->delete();

            return $item;
        }

        $item->update(['quantity' => $quantity]);

        return $item->refresh();
    }

    public function removeItem(AssistedCartItem $item): void
    {
        $this->assertEditable($item->cart);

        $item->delete();
    }

    public function setShipping(AssistedCart $cart, float $amount): AssistedCart
    {
        $this->assertEditable($cart);

        $cart->update(['shipping_amount' => max(0, $amount)]);

        return $cart->refresh();
    }

    /**
     * Apply a discount coupon. Returns the discount result.
     *
     * @return array{success: bool, amount: float, message: string}
     */
    public function applyDiscount(AssistedCart $cart, ?string $code): array
    {
        $this->assertEditable($cart);

        if (! $code) {
            $cart->update(['discount_code' => null, 'discount_amount' => 0]);

            return ['success' => true, 'amount' => 0.0, 'message' => 'Descuento eliminado.'];
        }

        $cart->load('items');
        $result = $this->discountService->applyCoupon($code, (float) $cart->subtotal);

        if (! $result || ! ($result['success'] ?? false)) {
            return ['success' => false, 'amount' => 0.0, 'message' => 'El código de descuento no es válido.'];
        }

        $cart->update([
            'discount_code' => $code,
            'discount_amount' => $result['amount'] ?? 0,
        ]);

        return [
            'success' => true,
            'amount' => (float) ($result['amount'] ?? 0),
            'message' => 'Descuento aplicado correctamente.',
        ];
    }

    public function clear(AssistedCart $cart): AssistedCart
    {
        $this->assertEditable($cart);

        $cart->items()->delete();
        $cart->update(['discount_code' => null, 'discount_amount' => 0]);

        return $cart->refresh()->load('items');
    }

    /**
     * Cancela (abandona) el carrito asistido: lo marca como abandonado para
     * que salga del flujo activo. No borra los ítems (queda como histórico de
     * carrito abandonado, visible en el panel de carritos del cliente).
     */
    public function cancel(AssistedCart $cart): AssistedCart
    {
        $this->assertEditable($cart);

        $cart->update(['status' => AssistedCart::STATUS_ABANDONED]);

        return $cart->refresh()->load('items');
    }

    /**
     * Convert the cart into a real Ecommerce order.
     *
     * @param  array<string, mixed>  $customerData
     */
    public function generateOrder(AssistedCart $cart, array $customerData, bool $markConverted = true): Order
    {
        // Idempotencia: un carrito genera como mucho UN pedido. Un doble clic
        // o retry de red no debe crear un segundo pedido real en Ecommerce
        // (dinero real). Si ya se generó, se devuelve ese mismo pedido.
        if ($cart->ecommerce_order_id) {
            return Order::findOrFail($cart->ecommerce_order_id);
        }

        // El lock cierra la ventana de concurrencia entre dos peticiones casi
        // simultáneas que ambas verían el carrito aún sin pedido.
        try {
            return Cache::lock("assisted-cart-order:{$cart->id}", 15)->block(5, function () use ($cart, $customerData, $markConverted) {
                $cart->refresh();

                if ($cart->ecommerce_order_id) {
                    return Order::findOrFail($cart->ecommerce_order_id);
                }

                return $this->createOrderForCart($cart, $customerData, $markConverted);
            });
        } catch (LockTimeoutException) {
            throw new RuntimeException('Ya se está generando el pedido de este carrito, inténtalo de nuevo.');
        }
    }

    /**
     * @param  array<string, mixed>  $customerData
     */
    private function createOrderForCart(AssistedCart $cart, array $customerData, bool $markConverted): Order
    {
        $this->assertEditable($cart);

        $cart->load('items');

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('El carrito no tiene productos.');
        }

        $ecommerceCustomer = $this->resolveEcommerceCustomer($cart, $customerData);

        $items = $cart->items->map(fn (AssistedCartItem $item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'product_image' => $item->image_url,
            'qty' => $item->quantity,
            'price' => (float) $item->unit_price,
            'total' => $item->line_total,
        ])->all();

        $order = DB::transaction(function () use ($cart, $ecommerceCustomer, $items, $customerData) {
            $order = $this->orderService->createOrder([
                'customer_id' => $ecommerceCustomer?->id,
                'status' => OrderStatus::PENDING->value,
                'sub_total' => $cart->subtotal,
                'tax_amount' => 0,
                'shipping_amount' => (float) $cart->shipping_amount,
                'discount_amount' => (float) $cart->discount_amount,
                'coupon_code' => $cart->discount_code,
                'total' => $cart->total,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'customer_note' => 'Pedido generado por un agente de soporte.',
            ], $items);

            OrderAddress::query()->create([
                'order_id' => $order->id,
                'type' => 'shipping',
                'name' => $customerData['name'] ?? '—',
                'phone' => $customerData['phone'] ?? null,
                'email' => $customerData['email'] ?? '',
                'address' => $customerData['address'] ?? '—',
                'city' => $customerData['city'] ?? '—',
                'state' => $customerData['state'] ?? null,
                'country' => $customerData['country'] ?? 'ES',
                'zip_code' => $customerData['zip_code'] ?? null,
            ]);

            return $order;
        });

        $cart->update([
            'ecommerce_order_id' => $order->id,
            'order_code' => $order->code,
            'status' => $markConverted ? AssistedCart::STATUS_ORDERED : $cart->status,
            'converted_at' => $markConverted ? now() : $cart->converted_at,
        ]);

        return $order;
    }

    /**
     * Generate an order (if needed) and email the customer a payment link.
     *
     * @param  array<string, mixed>  $customerData
     */
    public function sendPaymentLink(AssistedCart $cart, array $customerData): string
    {
        $order = $this->generateOrder($cart, $customerData, markConverted: false);

        $paymentUrl = route('payment.wompi.mobile-checkout', $order->token);

        $email = $customerData['email'] ?? $cart->customer?->email;

        if ($email) {
            SendHelpdeskEmailJob::dispatch(
                [$email],
                'Completa el pago de tu pedido '.$order->code,
                'helpdeskprestashop::emails.payment-link',
                [
                    'customerName' => $customerData['name'] ?? $cart->customer?->name ?? 'cliente',
                    'orderCode' => $order->code,
                    'total' => number_format((float) $cart->total, 2, ',', '.').' '.$cart->currency,
                    'paymentUrl' => $paymentUrl,
                ],
            );
        }

        $cart->update([
            'status' => AssistedCart::STATUS_SENT,
            'payment_url' => $paymentUrl,
            'sent_at' => now(),
        ]);

        return $paymentUrl;
    }

    /**
     * Build the JSON payload for a cart.
     *
     * @return array<string, mixed>
     */
    public function summarize(AssistedCart $cart): array
    {
        $cart->loadMissing('items');

        return [
            'id' => $cart->id,
            'status' => $cart->status,
            'status_label' => $cart->status_label,
            'status_group' => $cart->status_group,
            'currency' => $cart->currency,
            'discount_code' => $cart->discount_code,
            'discount_amount' => (float) $cart->discount_amount,
            'shipping_amount' => (float) $cart->shipping_amount,
            'subtotal' => (float) $cart->subtotal,
            'total' => (float) $cart->total,
            'items_count' => $cart->items->count(),
            'units_count' => $cart->units_count,
            'order_code' => $cart->order_code,
            'payment_url' => $cart->payment_url,
            'editable' => $cart->isEditable(),
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
            'items' => $cart->items->map(fn (AssistedCartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'sku' => $item->sku,
                'image_url' => $item->image_url,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->all(),
        ];
    }

    /**
     * Resolve (or create) the Ecommerce customer matching the helpdesk customer.
     *
     * @param  array<string, mixed>  $customerData
     */
    private function resolveEcommerceCustomer(AssistedCart $cart, array $customerData): ?EcommerceCustomer
    {
        $email = $customerData['email'] ?? $cart->customer?->email;

        if (! $email) {
            return null;
        }

        return EcommerceCustomer::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $customerData['name'] ?? $cart->customer?->name ?? 'Cliente',
                'phone' => $customerData['phone'] ?? $cart->customer?->phone,
                // El cliente de paso no inicia sesión con esta clave; aun así
                // usar aleatoriedad criptográfica (uniqid() no lo es).
                'password' => bcrypt(Str::random(40)),
                'status' => 'active',
            ],
        );
    }

    private function assertEditable(AssistedCart $cart): void
    {
        if (! $cart->isEditable()) {
            throw new RuntimeException('Este carrito ya no se puede modificar.');
        }
    }
}
