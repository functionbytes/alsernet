<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Resources\Api\V1\BrandDetailResource;
use Modules\Ecommerce\Http\Resources\Api\V1\BrandResource;
use Modules\Ecommerce\Http\Resources\Api\V1\ProductResource;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;

/**
 * @group Catálogo - Marcas
 *
 * Listado de marcas y sus productos. Acceso público.
 */
class BrandController extends BaseApiController
{
    /**
     * Listar marcas
     *
     * Devuelve la lista paginada de marcas publicadas con conteo de productos.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $brands = Brand::query()
            ->where('status', 'published')
            ->withCount('products')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate((int) request('per_page', 30));

        return $this->paginated($brands, BrandResource::class);
    }

    /**
     * Detalle de marca con productos
     *
     * Devuelve la marca con la lista paginada de sus productos.
     *
     * @unauthenticated
     *
     * @urlParam slug string required Slug de la marca. Example: nike
     */
    public function show(string $slug): JsonResponse
    {
        $brand = Brand::query()->where('slug', $slug)->firstOrFail();

        $products = Product::query()
            ->where('brand_id', $brand->id)
            ->with(['brand'])
            ->paginate((int) request('per_page', 15));

        $detail = (new BrandDetailResource($brand))->toArray(request());
        $detail['products'] = ProductResource::collection($products)->toArray(request());
        $detail['meta'] = [
            'currentPage' => $products->currentPage(),
            'perPage' => $products->perPage(),
            'total' => $products->total(),
            'lastPage' => $products->lastPage(),
        ];

        return $this->ok($detail);
    }
}
