<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\BrandResource;
use Modules\Ecommerce\Http\Resources\ProductCategoryResource;
use Modules\Ecommerce\Http\Resources\ProductResource;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Models\SearchLog;
use Modules\Ecommerce\Supports\ProductImageHelper;

class ProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->input('search');
        $category = $request->input('category');
        $perPage = (int) $request->input('per_page', 12);

        if ($query) {
            $products = Product::search($query)
                ->when($category, fn ($s) => $s->where('category_slug', $category))
                ->paginate($perPage);

            $products->load(['brand', 'categories']);
        } else {
            $products = Product::query()
                ->where('status', 'published')
                ->where('is_variation', false)
                ->with(['brand', 'categories'])
                ->when($category, fn ($q) => $q->whereHas('categories', fn ($q) => $q->where('slug', $category)))
                ->latest()
                ->paginate($perPage);
        }

        return response()->json(ProductResource::collection($products));
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(new ProductResource($product->load(['brand', 'categories', 'reviews'])));
    }

    public function categories(): JsonResponse
    {
        return response()->json(ProductCategoryResource::collection(ProductCategory::query()->where('status', 'published')->get()));
    }

    public function brands(): JsonResponse
    {
        return response()->json(BrandResource::collection(Brand::query()->where('status', 'published')->get()));
    }

    public function suggestions(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::search($q)
            ->take(8)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'image' => ProductImageHelper::url($p->featured_image, 'thumb', 'jpg'),
                'price' => $p->final_price,
                'url' => route('shop.product', $p->slug),
            ]);

        SearchLog::create([
            'query' => $q,
            'results_count' => $products->count(),
            'customer_id' => auth('ecommerce')->id(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['products' => $products]);
    }
}
