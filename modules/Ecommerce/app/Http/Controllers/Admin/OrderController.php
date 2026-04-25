<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Requests\Admin\StoreOrderRequest;
use Modules\Ecommerce\Http\Requests\Admin\UpdateOrderRequest;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;

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

        return view('ecommerce::admin.orders.index', compact('orders'));
    }

    public function create(): View
    {
        $customers = Customer::query()->pluck('name', 'id');
        $products = Product::query()->where('status', 'published')->get();

        return view('ecommerce::admin.orders.create', compact('customers', 'products'));
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
        $order->load(['customer', 'items.product']);

        return view('ecommerce::admin.orders.show', compact('order'));
    }

    public function edit(Order $order): View
    {
        $order->load(['customer', 'items.product']);

        return view('ecommerce::admin.orders.edit', compact('order'));
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
}
