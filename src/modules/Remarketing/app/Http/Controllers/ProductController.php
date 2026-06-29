<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Remarketing\Models\Product;
use Modules\Remarketing\Models\Store;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $products = Product::query()
            ->with('store')
            ->whereIn('store_id', $storeIds)
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->input('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->latest()
            ->paginate(20);

        return view('remarketing::products.index', compact('products'));
    }
}
