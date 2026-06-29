<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\WishlistResource;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;

class WishlistApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::query()
            ->with('product')
            ->where('customer_id', $request->user('sanctum')->id)
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json(WishlistResource::collection($wishlists));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        Wishlist::query()->firstOrCreate([
            'customer_id' => $request->user('sanctum')->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['message' => 'Producto agregado a favoritos.']);
    }

    public function destroy(Request $request, Wishlist $wishlist): JsonResponse
    {
        if ($wishlist->customer_id !== $request->user('sanctum')->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $wishlist->delete();

        return response()->json(['message' => 'Producto eliminado de favoritos.']);
    }
}
