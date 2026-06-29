<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Http\Filters\Api\V1\ProductFilter;
use Modules\Ecommerce\Http\Requests\Api\V1\Catalog\ListProductsRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\ProductDetailResource;
use Modules\Ecommerce\Http\Resources\Api\V1\ProductResource;
use Modules\Ecommerce\Models\Product;

/**
 * @group Catálogo - Productos
 *
 * Listado, búsqueda y detalle de productos. Acceso público (sin autenticación).
 */
class CatalogController extends BaseApiController
{
    /**
     * Listar productos
     *
     * Devuelve la lista paginada de productos con soporte para búsqueda full-text y filtros.
     *
     * @unauthenticated
     *
     * @queryParam q string Término de búsqueda (full-text). Example: camiseta azul
     * @queryParam filter[brand] integer Filtrar por ID de marca. Example: 3
     * @queryParam filter[category] integer Filtrar por ID de categoría. Example: 12
     * @queryParam filter[priceMin] number Precio mínimo. Example: 10.00
     * @queryParam filter[priceMax] number Precio máximo. Example: 150.00
     * @queryParam filter[inStock] boolean Solo productos con stock. Example: true
     * @queryParam sort string Ordenar: price,-price,name,-name,newest. Example: -price
     * @queryParam per_page integer Items por página (máx 50). Example: 15
     */
    public function index(ListProductsRequest $request): JsonResponse
    {
        $filter = new ProductFilter($request);

        $query = $request->filled('q')
            ? Product::search($request->string('q')->toString())->query(fn ($q) => $filter->apply($q))
            : $filter->apply(Product::query());

        $products = $query->paginate($filter->perPage());

        return $this->paginated($products, ProductResource::class);
    }

    /**
     * Detalle de producto
     *
     * Devuelve el detalle completo de un producto por su slug, incluyendo marca, categorías y estadísticas de reseñas.
     *
     * @unauthenticated
     *
     * @urlParam slug string required Slug del producto. Example: camiseta-polo-azul
     */
    public function show(string $slug): JsonResponse
    {
        $cacheKey = 'mobile-api:product:'.app()->getLocale().':'.$slug;

        $product = Cache::remember($cacheKey, 600, function () use ($slug) {
            return Product::query()
                ->where('slug', $slug)
                ->with(['brand', 'categories', 'translations'])
                ->withCount('reviews')
                ->withAvg('reviews', 'star')
                ->firstOrFail();
        });

        return $this->ok(new ProductDetailResource($product));
    }

    /**
     * Productos relacionados
     *
     * Devuelve hasta 8 productos de las mismas categorías del producto indicado.
     *
     * @unauthenticated
     *
     * @urlParam slug string required Slug del producto. Example: camiseta-polo-azul
     */
    public function related(string $slug): JsonResponse
    {
        $product = Product::query()->where('slug', $slug)->with('categories')->firstOrFail();
        $categoryIds = $product->categories->pluck('id');

        $related = Product::query()
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('ecommerce_product_categories.id', $categoryIds))
            ->with(['brand'])
            ->latest()
            ->limit(8)
            ->get();

        return $this->ok(ProductResource::collection($related)->toArray(request()));
    }

    /**
     * Sugerencias de búsqueda
     *
     * Devuelve hasta 8 sugerencias de productos para autocompletar. Requiere mínimo 2 caracteres.
     *
     * @unauthenticated
     *
     * @queryParam q string required Término de búsqueda (mín 2 chars). Example: polo
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:120']]);

        $q = $request->string('q')->toString();

        $suggestions = Product::search($q)
            ->take(8)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
            ])
            ->values();

        return $this->ok($suggestions);
    }
}
