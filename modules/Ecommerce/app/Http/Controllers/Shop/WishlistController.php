<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Http\Middleware\RedirectIfNotCustomer;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;

class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware(RedirectIfNotCustomer::class);
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
}
