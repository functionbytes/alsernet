<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Http\Resources\Api\V1\ProductResource;
use Modules\Ecommerce\Models\Product;

/**
 * @group Historial de vistos
 *
 * Productos recientemente vistos por el cliente.
 */
class RecentlyViewedController extends BaseApiController
{
    /**
     * Listar productos vistos recientemente
     *
     * Devuelve los últimos productos visitados por el cliente.
     *
     * @queryParam limit integer Número máximo de resultados (máx 20). Example: 12
     */
    public function index(): JsonResponse
    {
        $products = auth()->user()
            ->recentlyViewed()
            ->with('brand')
            ->limit((int) request('limit', 12))
            ->get();

        return $this->ok(ProductResource::collection($products)->toArray(request()));
    }

    /**
     * Registrar producto visto
     *
     * Añade o actualiza el producto en el historial de vistos del cliente.
     * Mantiene un máximo de 20 productos recientes (elimina los más antiguos).
     *
     * @urlParam product integer required ID del producto. Example: 5
     */
    public function track(Product $product, Request $request): JsonResponse
    {
        $customer = $request->user();

        DB::table('ecommerce_customer_recently_viewed_products')
            ->updateOrInsert(
                ['customer_id' => $customer->id, 'product_id' => $product->id],
                ['updated_at' => now(), 'created_at' => now()]
            );

        $limit = (int) config('ecommerce.checkout_recently_viewed_max', 20);

        $idsToKeep = DB::table('ecommerce_customer_recently_viewed_products')
            ->where('customer_id', $customer->id)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->pluck('id');

        DB::table('ecommerce_customer_recently_viewed_products')
            ->where('customer_id', $customer->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        return $this->noContent();
    }
}
