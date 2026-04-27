<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;

class ProductFilterApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->where('status', 'published');

        $query->when($request->input('category_id'), fn ($q, $id) => $q->whereHas(
            'categories', fn ($q) => $q->where('id', $id)
        ));

        $query->when($request->input('brand_id'), fn ($q, $id) => $q->where('brand_id', $id));

        $query->when($request->input('min_price'), fn ($q, $price) => $q->where('price', '>=', $price));

        $query->when($request->input('max_price'), fn ($q, $price) => $q->where('price', '<=', $price));

        $priceRange = (clone $query)->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        $brands = Brand::query()
            ->where('status', 'published')
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $categories = ProductCategory::query()
            ->where('status', 'published')
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'priceRange' => [
                'min' => (float) ($priceRange->min_price ?? 0),
                'max' => (float) ($priceRange->max_price ?? 0),
            ],
            'brands' => $brands->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'count' => $b->products_count,
            ])->values(),
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'count' => $c->products_count,
            ])->values(),
        ]);
    }
}
