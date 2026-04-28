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

class CatalogController extends BaseApiController
{
    public function index(ListProductsRequest $request): JsonResponse
    {
        $filter = new ProductFilter($request);

        $query = $request->filled('q')
            ? Product::search($request->string('q')->toString())->query(fn ($q) => $filter->apply($q))
            : $filter->apply(Product::query());

        $products = $query->paginate($filter->perPage());

        return $this->paginated($products, ProductResource::class);
    }

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
