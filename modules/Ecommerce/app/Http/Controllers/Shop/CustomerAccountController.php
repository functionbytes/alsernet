<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Ecommerce\Enums\ProductStatus;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CustomerAddress;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\ProductRecommendationService;

class CustomerAccountController extends Controller
{
    public function __construct(
        private readonly ProductRecommendationService $recommendations
    ) {}

    public function dashboard(): View
    {
        $customer = auth('ecommerce')->user();
        $recentOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['items', 'payment'])
            ->latest()
            ->limit(5)
            ->get();

        $recommendations = $this->recommendations->suggestForCustomer($customer, 4);

        return view('ecommerce::shop.account.dashboard', compact('customer', 'recentOrders', 'recommendations'));
    }

    public function orders(): View
    {
        $customer = auth('ecommerce')->user();
        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['items', 'payment'])
            ->latest()
            ->paginate(10);

        return view('ecommerce::shop.account.orders', compact('orders'));
    }

    public function orderDetail(Order $order): View|RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        if ($order->customer_id !== $customer->id) {
            return redirect()->route('account.orders')->with('error', 'No tienes acceso a esta orden.');
        }

        $order->load(['items.product', 'shippingAddress', 'shipments', 'histories', 'payment']);

        return view('ecommerce::shop.account.order-detail', compact('order'));
    }

    public function profile(): View
    {
        $customer = auth('ecommerce')->user();

        return view('ecommerce::shop.account.profile', compact('customer'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $customer->update($validated);

        return redirect()->route('account.profile')->with('success', 'Perfil actualizado correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->input('current_password'), $customer->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
        }

        $customer->update(['password' => Hash::make($request->input('password'))]);

        return redirect()->route('account.profile')->with('success', 'Contraseña actualizada correctamente.');
    }

    public function addresses(): View
    {
        $customer = auth('ecommerce')->user();
        $addresses = $customer->addresses()->orderByDesc('is_default')->get();

        return view('ecommerce::shop.account.addresses', compact('addresses'));
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $customer->addresses()->create($validated);

        return redirect()->route('account.addresses')->with('success', 'Dirección guardada correctamente.');
    }

    public function destroyAddress(CustomerAddress $address): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        if ($address->customer_id !== $customer->id) {
            return redirect()->route('account.addresses')->with('error', 'No tienes acceso a esta dirección.');
        }

        $address->delete();

        return redirect()->route('account.addresses')->with('success', 'Dirección eliminada.');
    }

    public function reorder(Order $order): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        if ($order->customer_id !== $customer->id) {
            return redirect()->route('account.orders')->with('error', 'No tienes acceso a esta orden.');
        }

        $order->load('items.product');
        $added = 0;
        $skipped = 0;

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product || $product->status !== ProductStatus::PUBLISHED) {
                $skipped++;

                continue;
            }

            $isOutOfStock = $product->with_storehouse_management
                && $product->quantity <= 0
                && ! $product->allow_checkout_when_out_of_stock;

            if ($isOutOfStock) {
                $skipped++;

                continue;
            }

            $qty = $product->with_storehouse_management
                ? min($item->qty, $product->quantity)
                : $item->qty;

            Cart::query()->updateOrCreate(
                ['customer_id' => $customer->id, 'product_id' => $product->id],
                ['qty' => $qty]
            );

            $added++;
        }

        $message = "{$added} producto(s) agregado(s) al carrito.";

        if ($skipped > 0) {
            $message .= " {$skipped} no disponible(s).";
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    public function setDefaultAddress(CustomerAddress $address): RedirectResponse
    {
        $customer = auth('ecommerce')->user();

        if ($address->customer_id !== $customer->id) {
            return redirect()->route('account.addresses')->with('error', 'No tienes acceso a esta dirección.');
        }

        $customer->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('account.addresses')->with('success', 'Dirección principal actualizada.');
    }
}
