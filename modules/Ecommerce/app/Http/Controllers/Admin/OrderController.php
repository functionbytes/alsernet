<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Enums\OrderAddressType;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Events\OrderPaymentConfirmed;
use Modules\Ecommerce\Events\ShippingStatusChanged;
use Modules\Ecommerce\Http\Requests\Admin\StoreOrderRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateOrderRequest;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Shipment;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Services\OrderEmailService;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('customer')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), fn ($q, $search) => $q->where('code', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20);

        return view('ecommerce::orders.index', compact('orders'));
    }

    public function create(): View
    {
        $customers = Customer::query()->pluck('name', 'id');
        $products = Product::query()->where('status', 'published')->get();

        return view('ecommerce::orders.create', compact('customers', 'products'));
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = Order::query()->create($request->only([
            'customer_id', 'status', 'sub_total', 'tax_amount', 'shipping_amount',
            'discount_amount', 'total', 'payment_method', 'customer_note', 'admin_note',
        ]));

        foreach ($request->input('items', []) as $item) {
            $product = Product::query()->find($item['product_id']);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $product?->name ?? 'Producto',
                'qty' => $item['qty'],
                'price' => $item['price'],
                'total' => $item['qty'] * $item['price'],
            ]);
        }

        return redirect()->route('ecommerce.orders.index')->with('success', 'Orden creada exitosamente.');
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'shippingAddress', 'histories.user', 'shipments', 'payment']);

        return view('ecommerce::orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        $order->load(['customer', 'items.product']);

        return view('ecommerce::orders.edit', compact('order'));
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

        return redirect()->route('ecommerce.orders.index')->with('success', 'Orden actualizada exitosamente.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('ecommerce.orders.index')->with('success', 'Orden eliminada exitosamente.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $order->update(['status' => $request->input('status')]);

        return redirect()->back()->with('success', 'Estado de orden actualizado.');
    }

    public function confirmPayment(Order $order): RedirectResponse
    {
        if ($order->payment_status === 'paid') {
            return back()->with('error', 'El pago ya fue confirmado.');
        }

        $order->update(['payment_status' => 'paid']);

        OrderPaymentConfirmed::dispatch($order);

        return back()->with('success', 'Pago confirmado exitosamente.');
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $adminNote = $order->admin_note ?? '';

        if ($request->filled('cancel_reason')) {
            $adminNote = trim($adminNote."\n".$request->input('cancel_reason'));
        }

        $order->update([
            'status' => OrderStatus::CANCELLED->value,
            'admin_note' => $adminNote ?: null,
        ]);

        OrderCancelled::dispatch($order);

        return back()->with('success', 'Orden cancelada exitosamente.');
    }

    public function addNote(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $order->update(['admin_note' => $request->input('admin_note')]);

        return back()->with('success', 'Nota actualizada exitosamente.');
    }

    public function updateDelivery(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'delivery_date' => ['nullable', 'date'],
            'delivery_time' => ['nullable', 'date_format:H:i'],
        ]);

        $order->update([
            'delivery_date' => $request->input('delivery_date'),
            'delivery_time' => $request->input('delivery_time'),
        ]);

        return back()->with('success', 'Fecha de entrega actualizada exitosamente.');
    }

    public function resendConfirmation(Order $order): RedirectResponse
    {
        if (! $order->customer) {
            return back()->with('error', 'Esta orden no tiene un cliente asociado.');
        }

        OrderEmailService::sendOrderCreated($order);

        return back()->with('success', 'Correo de confirmacion reenviado exitosamente.');
    }

    public function createShipment(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $shipment = Shipment::query()->create([
            'order_id' => $order->id,
            'note' => $request->input('note'),
            'weight' => $request->input('weight', 0),
            'status' => 'processing',
        ]);

        if ($order->status === OrderStatus::PENDING->value) {
            $order->update(['status' => OrderStatus::PROCESSING->value]);
        }

        ShippingStatusChanged::dispatch($shipment, '', $shipment->status);

        return redirect()
            ->route('ecommerce.shipments.show', $shipment)
            ->with('success', 'Envio creado exitosamente.');
    }

    public function reorder(Order $order): View
    {
        $order->load(['items.product', 'customer', 'shippingAddress']);
        $customers = Customer::query()->pluck('name', 'id');
        $products = Product::query()->where('status', 'published')->get();

        return view('ecommerce::orders.reorder', compact('order', 'customers', 'products'));
    }

    public function manage(Order $order): View
    {
        $order->load(['items.product', 'customer', 'shippingAddress']);
        $products = Product::query()->where('status', 'published')->get();

        return view('ecommerce::orders.manage', compact('order', 'products'));
    }

    public function saveManage(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'sub_total' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->items()->delete();

        foreach ($request->input('items', []) as $item) {
            $product = Product::query()->find($item['product_id']);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $product?->name ?? 'Producto',
                'qty' => $item['qty'],
                'price' => $item['price'],
                'total' => $item['qty'] * $item['price'],
            ]);
        }

        $order->update([
            'sub_total' => $request->input('sub_total'),
            'shipping_amount' => $request->input('shipping_amount', 0),
            'tax_amount' => $request->input('tax_amount', 0),
            'discount_amount' => $request->input('discount_amount', 0),
            'total' => $request->input('total'),
            'payment_method' => $request->input('payment_method'),
            'transaction_id' => $request->input('transaction_id'),
            'payment_status' => $request->input('payment_status'),
            'customer_note' => $request->input('customer_note'),
        ]);

        return redirect()->route('ecommerce.orders.show', $order)
            ->with('success', 'Orden actualizada exitosamente.');
    }

    public function updateCustomerNote(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update(['customer_note' => $request->input('customer_note')]);

        return back()->with('success', 'Nota del cliente actualizada.');
    }

    public function updateShippingAddress(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        $order->addresses()->updateOrCreate(
            ['type' => OrderAddressType::SHIPPING->value],
            $request->only(['name', 'phone', 'email', 'address', 'city', 'state', 'country'])
        );

        return back()->with('success', 'Direccion de envio actualizada.');
    }

    public function calculateDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $discount = Discount::query()
            ->where('code', $request->input('code'))
            ->where('is_active', true)
            ->first();

        if (! $discount) {
            return response()->json(['message' => 'El código de descuento no es válido.'], 422);
        }

        if ($discount->isExpired()) {
            return response()->json(['message' => 'El código de descuento ha expirado o alcanzó su límite de usos.'], 422);
        }

        $subtotal = (float) $request->input('subtotal');
        $amount = $discount->calculateDiscount($subtotal);

        if ($amount <= 0) {
            $minPrice = number_format((float) $discount->min_order_price, 2);

            return response()->json([
                'message' => "El pedido no cumple el monto mínimo de \${$minPrice} para aplicar este descuento.",
            ], 422);
        }

        $description = match ($discount->type) {
            DiscountType::PERCENTAGE => "{$discount->value}% de descuento",
            DiscountType::FIXED => '$'.number_format($amount, 2).' de descuento',
            DiscountType::FREE_SHIPPING => 'Envío gratis',
            default => 'Descuento aplicado',
        };

        return response()->json([
            'success' => true,
            'discount_amount' => $amount,
            'description' => $description,
            'code' => $discount->code,
        ]);
    }

    public function getShippingOptions(Request $request): JsonResponse
    {
        $subtotal = (float) $request->input('subtotal', 0);

        $shippings = Shipping::query()
            ->where('is_active', true)
            ->with('rules')
            ->get();

        $options = $shippings->map(function (Shipping $shipping) use ($subtotal) {
            $price = $this->resolveShippingPrice($shipping, $subtotal);

            return [
                'id' => $shipping->id,
                'title' => $shipping->title,
                'price' => $price,
                'description' => $price > 0 ? '$'.number_format($price, 2) : 'Envío gratis',
            ];
        });

        return response()->json($options->values());
    }

    private function resolveShippingPrice(Shipping $shipping, float $subtotal): float
    {
        $priceRules = $shipping->rules->where('type', 'price');

        if ($priceRules->isEmpty()) {
            return 0.0;
        }

        foreach ($priceRules as $rule) {
            $from = (float) $rule->from;
            $to = (float) $rule->to;

            $aboveFrom = $subtotal >= $from;
            $belowTo = $to <= 0 || $subtotal <= $to;

            if ($aboveFrom && $belowTo) {
                return (float) $rule->price;
            }
        }

        return 0.0;
    }

    public function applyDiscountCode(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $discount = Discount::query()
            ->where('code', $request->input('code'))
            ->where('is_active', true)
            ->first();

        if (! $discount) {
            return response()->json(['message' => 'El código de descuento no es válido.'], 422);
        }

        if ($discount->isExpired()) {
            return response()->json(['message' => 'El código de descuento ha expirado o alcanzó su límite de usos.'], 422);
        }

        $discountAmount = $discount->calculateDiscount((float) $order->sub_total);

        if ($discountAmount <= 0) {
            $minPrice = number_format((float) $discount->min_order_price, 2);

            return response()->json([
                'message' => "El pedido no cumple el monto mínimo de \${$minPrice} para aplicar este descuento.",
            ], 422);
        }

        $newTotal = max(0, (float) $order->sub_total + (float) $order->tax_amount + (float) $order->shipping_amount - $discountAmount);

        $order->update([
            'coupon_code' => $discount->code,
            'discount_amount' => $discountAmount,
            'total' => $newTotal,
        ]);

        return response()->json([
            'success' => true,
            'discount_amount' => $discountAmount,
            'total' => $newTotal,
        ]);
    }
}
