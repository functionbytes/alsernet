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

class ProductApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('status', 'published')
            ->with(['brand', 'categories'])
            ->when($request->input('category'), function ($q, $category) {
                $q->whereHas('categories', fn ($q) => $q->where('slug', $category));
            })
            ->when($request->input('search'), function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->paginate($request->input('per_page', 12));

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
}
