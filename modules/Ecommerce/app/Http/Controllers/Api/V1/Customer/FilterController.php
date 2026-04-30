<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Http\Resources\Api\V1\BrandResource;
use Modules\Ecommerce\Http\Resources\Api\V1\CategoryResource;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;

/**
 * @group Catálogo - Filtros
 *
 * Opciones de filtrado disponibles (marcas, categorías, rango de precios). Acceso público.
 */
class FilterController extends BaseApiController
{
    /**
     * Opciones de filtrado
     *
     * Devuelve todas las opciones disponibles para filtrar productos: marcas, categorías y rango de precios.
     * Respuesta cacheada 10 minutos por locale.
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $payload = Cache::remember('mobile-api:filters:'.app()->getLocale(), 600, function () {
            $brands = Brand::query()
                ->where('status', 'published')
                ->orderBy('name')
                ->limit(100)
                ->get();

            $categories = ProductCategory::query()
                ->whereNull('parent_id')
                ->where('status', 'published')
                ->with('children')
                ->orderBy('order')
                ->get();

            return [
                'brands' => BrandResource::collection($brands)->toArray(request()),
                'categories' => CategoryResource::collection($categories)->toArray(request()),
                'priceRange' => [
                    'min' => (float) Product::query()->min('price'),
                    'max' => (float) Product::query()->max('price'),
                ],
            ];
        });

        return $this->ok($payload);
    }
}
