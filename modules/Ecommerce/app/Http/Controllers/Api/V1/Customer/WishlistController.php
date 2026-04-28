<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Resources\Api\V1\WishlistItemResource;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Wishlist;

class WishlistController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $items = Wishlist::query()
            ->where('customer_id', auth()->id())
            ->with('product.brand')
            ->latest()
            ->paginate((int) request('per_page', 15));

        return $this->paginated($items, WishlistItemResource::class);
    }

    public function store(Product $product): JsonResponse
    {
        $item = Wishlist::query()->firstOrCreate([
            'customer_id' => auth()->id(),
            'product_id' => $product->id,
        ]);

        $item->load('product.brand');

        return $this->created(new WishlistItemResource($item));
    }

    public function destroy(Wishlist $wishlist): JsonResponse
    {
        if ($wishlist->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $wishlist->delete();

        return $this->noContent('Eliminado de favoritos.');
    }
}
