<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Services\CartService;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(): View
    {
        $cartItems = $this->cartService->getCartItems();
        $total = $cartItems->sum(fn ($item) => $item->product->final_price * $item->qty);

        return view('ecommerce::shop.cart', compact('cartItems', 'total'));
    }

    public function add(Product $product, Request $request): RedirectResponse
    {
        $qty = max(1, (int) $request->input('qty', 1));
        $customerId = auth('ecommerce')->id();
        $sessionId = session()->getId();

        $existing = Cart::query()
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when(! $customerId, fn ($q) => $q->where('session_id', $sessionId))
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->update(['qty' => $existing->qty + $qty]);
        } else {
            Cart::query()->create([
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'product_id' => $product->id,
                'qty' => $qty,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $cartItem = Cart::query()->findOrFail($id);
        $cartItem->update(['qty' => max(1, (int) $request->input('qty', 1))]);

        return redirect()->route('cart.index')->with('success', 'Carrito actualizado.');
    }

    public function remove(int $id): RedirectResponse
    {
        $cartItem = Cart::query()->findOrFail($id);
        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Producto removido del carrito.');
    }

    public function clear(): RedirectResponse
    {
        $this->cartService->clearCart();

        return redirect()->route('cart.index')->with('success', 'Carrito vaciado.');
    }
}
