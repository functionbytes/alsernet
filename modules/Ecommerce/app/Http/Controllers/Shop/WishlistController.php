<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Middleware\RedirectIfNotCustomer;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;

class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware(RedirectIfNotCustomer::class)->except('shared');
    }

    public function index(): View
    {
        $wishlists = Wishlist::query()
            ->with('product')
            ->where('customer_id', auth('ecommerce')->id())
            ->latest()
            ->paginate(20);

        return view('ecommerce::shop.wishlist.index', compact('wishlists'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        Wishlist::query()->firstOrCreate([
            'customer_id' => auth('ecommerce')->id(),
            'product_id' => $product->id,
        ]);

        return back()->with('success', 'Producto agregado a favoritos.');
    }

    public function destroy(Wishlist $wishlist): RedirectResponse
    {
        $wishlist->delete();

        return back()->with('success', 'Producto eliminado de favoritos.');
    }

    public function getShareUrl(): JsonResponse
    {
        $customer = auth('ecommerce')->user();

        if (! $customer->wishlist_share_token) {
            $customer->update(['wishlist_share_token' => Str::random(40)]);
        }

        return response()->json([
            'url' => route('shop.wishlist.shared', $customer->wishlist_share_token),
        ]);
    }

    public function shared(string $token): View
    {
        $customer = Customer::query()
            ->where('wishlist_share_token', $token)
            ->firstOrFail();

        $items = $customer->wishlists()->with('product.categories')->get();

        return view('ecommerce::shop.wishlist.shared', compact('customer', 'items'));
    }
}
