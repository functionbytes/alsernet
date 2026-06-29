<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Bundle;
use Modules\Ecommerce\Models\Cart;

class BundleController extends Controller
{
    public function index(): View
    {
        $bundles = Bundle::query()
            ->with('products')
            ->where('status', 'published')
            ->latest()
            ->paginate(12);

        return view('ecommerce::shop.bundles.index', compact('bundles'));
    }

    public function show(string $slug): View
    {
        $bundle = Bundle::query()
            ->with('products')
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('ecommerce::shop.bundles.show', compact('bundle'));
    }

    public function addToCart(Bundle $bundle, Request $request): RedirectResponse
    {
        $bundle->load('products');

        if ($bundle->status !== 'published') {
            return back()->with('error', 'Este bundle no está disponible.');
        }

        $customerId = auth('ecommerce')->id();
        $sessionId = session()->getId();

        foreach ($bundle->products as $product) {
            $qty = $product->pivot->qty;

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
        }

        return redirect()->route('cart.index')->with('success', "Bundle \"{$bundle->name}\" agregado al carrito.");
    }
}
