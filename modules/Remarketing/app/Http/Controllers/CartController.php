<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Store;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Cart::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $carts = Cart::query()
            ->with(['customer', 'store'])
            ->whereIn('store_id', $storeIds)
            ->where('status', 'abandoned')
            ->when($request->input('from'), fn ($q, $d) => $q->where('abandoned_at', '>=', $d))
            ->when($request->input('to'), fn ($q, $d) => $q->where('abandoned_at', '<=', $d))
            ->when($request->input('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->latest('abandoned_at')
            ->paginate(20);

        return view('remarketing::carts.index', compact('carts'));
    }
}
