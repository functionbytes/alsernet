<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderHistory;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\OrderStockService;
use Modules\Ecommerce\Services\ShippingService;
use Modules\Ecommerce\Services\TaxService;
use Modules\EcommercePayment\Mail\OrderConfirmationMail;
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

        $paymentMethods = $this->gatewayManager->all();

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
            'region' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'string'],
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
        $total = $subtotal + $shipping + $tax;

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
            'discount_amount' => 0,
            'total' => $total,
            'payment_method' => $validated['payment_method'],
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

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => null,
            'action' => 'create_order',
            'description' => 'Orden creada desde la tienda.',
        ]);

        // Send order confirmation email
        if ($order->customer && $order->customer->email) {
            Mail::to($order->customer->email)->send(new OrderConfirmationMail($order));
        }

        // If card selected and Wompi is enabled, route to Wompi
        $paymentMethod = $validated['payment_method'];
        if ($paymentMethod === 'card' && $this->gatewayManager->has('wompi')) {
            $wompiGateway = $this->gatewayManager->get('wompi');
            if (method_exists($wompiGateway, 'isEnabled') && $wompiGateway->isEnabled()) {
                $paymentMethod = 'wompi';
                $order->update(['payment_method' => 'wompi']);
            }
        }

        // If external gateway payment, redirect to payment processor
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
                'region' => $validated['region'],
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

    public function retryPayment(Order $order)
    {
        if ($order->payment_status !== 'pending' && $order->payment_status !== 'failed') {
            return redirect()->route('checkout.confirmation', $order)
                ->with('error', 'Esta orden no puede ser reintentada.');
        }

        if (! $this->gatewayManager->has('wompi')) {
            return redirect()->route('shop.index')
                ->with('error', 'No hay pasarela de pago disponible.');
        }

        $gateway = $this->gatewayManager->get('wompi');

        if (method_exists($gateway, 'isEnabled') && ! $gateway->isEnabled()) {
            return redirect()->route('shop.index')
                ->with('error', 'El metodo de pago no esta habilitado.');
        }

        $customerData = [
            'email' => $order->customer->email ?? '',
            'name' => $order->customer->name ?? '',
            'phone' => $order->customer->phone ?? '',
            'address' => '',
            'city' => '',
            'region' => '',
        ];

        return $gateway->makePayment($order, $customerData);
    }
}
