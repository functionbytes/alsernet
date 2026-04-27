<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Enums\OrderAddressType;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Events\OrderCreated;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderAddress;
use Modules\Ecommerce\Models\OrderHistory;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\OrderStockService;
use Modules\Ecommerce\Services\ShippingService;
use Modules\Ecommerce\Services\TaxService;
use Modules\EcommercePayment\Services\PaymentGatewayManager;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected ShippingService $shippingService,
        protected TaxService $taxService,
        protected PaymentGatewayManager $gatewayManager,
        protected OrderStockService $orderStockService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cartItems = $this->cartService->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index')->with('error', 'Tu carrito esta vacio.');
        }

        $subtotal = $cartItems->sum(fn ($item) => $item->product->final_price * $item->qty);
        $shipping = $this->shippingService->calculateShipping($subtotal);
        $tax = $this->taxService->calculateTax($subtotal);
        $total = $subtotal + $shipping + $tax;

        $paymentMethods = collect($this->gatewayManager->all())
            ->map(fn ($class, $key) => $this->gatewayManager->get($key))
            ->filter(fn ($gateway) => $gateway->isEnabled())
            ->map(fn ($gateway) => [
                'key' => $gateway->getChannel(),
                'name' => $gateway->getName(),
                'description' => $gateway->getDescription(),
            ])
            ->values();

        return view('ecommerce::shop.checkout', compact('cartItems', 'subtotal', 'shipping', 'tax', 'total', 'paymentMethods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $cartItems = $this->cartService->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index')->with('error', 'Tu carrito esta vacio.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:255'],
            'city_id' => ['nullable', 'integer'],
            'region' => ['nullable', 'string', 'max:255'],
            'state_id' => ['nullable', 'integer'],
            'country' => ['required', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->gatewayManager->has($value)) {
                        $fail('El método de pago seleccionado no está disponible.');

                        return;
                    }
                    if (! $this->gatewayManager->get($value)->isEnabled()) {
                        $fail('El método de pago seleccionado no está habilitado.');
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if ($product->with_storehouse_management && $product->quantity < $cartItem->qty && ! $product->allow_checkout_when_out_of_stock) {
                return redirect()->route('cart.index')->with('error', "No hay suficiente stock para {$product->name}.");
            }
        }

        $subtotal = $cartItems->sum(fn ($item) => $item->product->final_price * $item->qty);
        $shipping = $this->shippingService->calculateShipping($subtotal, $validated['country'] ?? null);
        $tax = $this->taxService->calculateTax($subtotal + $shipping, $validated['country'] ?? null);

        $paymentMethod = $validated['payment_method'];
        $fee = 0.0;
        if ($this->gatewayManager->has($paymentMethod)) {
            $fee = $this->gatewayManager->get($paymentMethod)->getFee($subtotal);
        }

        $total = $subtotal + $shipping + $tax + $fee;

        $discountAmount = 0.0;
        $couponCode = $validated['coupon_code'] ?? null;
        $discount = null;

        if ($couponCode) {
            $discount = Discount::query()
                ->where('code', $couponCode)
                ->where('is_active', true)
                ->first();

            if ($discount && ! $discount->isExpired()) {
                $discountAmount = $discount->calculateDiscount($subtotal);
                $total = max(0, $total - $discountAmount);
            }
        }

        $customer = auth('ecommerce')->user();
        if (! $customer) {
            $customer = Customer::query()->firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'password' => bcrypt(uniqid()),
                    'phone' => $validated['phone'],
                    'status' => 'active',
                ]
            );
        }

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING,
            'sub_total' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'discount_amount' => $discountAmount,
            'coupon_code' => $couponCode,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'customer_note' => $request->input('note'),
        ]);

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->featured_image,
                'qty' => $cartItem->qty,
                'price' => $product->final_price,
                'total' => $product->final_price * $cartItem->qty,
            ]);

            if ($product->with_storehouse_management) {
                $product->decrement('quantity', $cartItem->qty);
                if ($product->quantity <= 0) {
                    $product->update(['stock_status' => 'out_of_stock']);
                }
            }
        }

        OrderAddress::query()->create([
            'order_id' => $order->id,
            'type' => OrderAddressType::SHIPPING->value,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['region'] ?? null,
            'country' => $validated['country'],
            'zip_code' => $validated['zip_code'] ?? null,
            'country_id' => $validated['country_id'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
        ]);

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => null,
            'action' => 'create_order',
            'description' => 'Orden creada desde la tienda.',
        ]);

        if ($discount && $discountAmount > 0) {
            $discount->increment('total_used');
        }

        OrderCreated::dispatch($order);

        if ($this->gatewayManager->has($paymentMethod)) {
            $gateway = $this->gatewayManager->get($paymentMethod);

            if (method_exists($gateway, 'isEnabled') && ! $gateway->isEnabled()) {
                $this->orderStockService->restoreStock($order);
                $order->update(['status' => 'canceled']);

                return redirect()->route('checkout.index')->with('error', 'El metodo de pago seleccionado no esta habilitado.');
            }

            $customerData = [
                'email' => $validated['email'],
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? '',
                'address' => $validated['address'],
                'city' => $validated['city'],
                'region' => $validated['region'] ?? '',
            ];

            return $gateway->makePayment($order, $customerData);
        }

        $this->cartService->clearCart();

        return redirect()->route('checkout.confirmation', $order)->with('success', 'Orden realizada exitosamente.');
    }

    public function confirmation(Order $order): View
    {
        $order->load('items');

        return view('ecommerce::shop.checkout_success', compact('order'));
    }

    public function retryPayment(Order $order): RedirectResponse
    {
        if ($order->payment_status !== 'pending' && $order->payment_status !== 'failed') {
            return redirect()->route('checkout.confirmation', $order)
                ->with('error', 'Esta orden no puede ser reintentada.');
        }

        $paymentMethod = $order->payment_method;

        if (! $this->gatewayManager->has($paymentMethod)) {
            return redirect()->route('shop.index')
                ->with('error', 'El método de pago de esta orden no está disponible.');
        }

        $gateway = $this->gatewayManager->get($paymentMethod);

        if (! $gateway->isEnabled()) {
            return redirect()->route('shop.index')
                ->with('error', 'El método de pago no está habilitado.');
        }

        $shippingAddress = $order->shippingAddress;

        $customerData = [
            'email' => $order->customer->email ?? '',
            'name' => $order->customer->name ?? '',
            'phone' => $shippingAddress?->phone ?? $order->customer->phone ?? '',
            'address' => $shippingAddress?->address ?? '',
            'city' => $shippingAddress?->city ?? '',
            'region' => $shippingAddress?->state ?? '',
        ];

        return $gateway->makePayment($order, $customerData);
    }
}
