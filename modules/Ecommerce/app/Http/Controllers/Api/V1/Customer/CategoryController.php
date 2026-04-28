<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Resources\Api\V1\CategoryDetailResource;
use Modules\Ecommerce\Http\Resources\Api\V1\CategoryResource;
use Modules\Ecommerce\Http\Resources\Api\V1\ProductResource;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;

class CategoryController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $tree = ProductCategory::query()
            ->whereNull('parent_id')
            ->where('status', 'published')
            ->with('children.children')
            ->orderBy('order')
            ->get();

        return $this->ok(CategoryResource::collection($tree)->toArray(request()));
    }

    public function show(string $slug): JsonResponse
    {
        $category = ProductCategory::query()->where('slug', $slug)->firstOrFail();

        $products = Product::query()
            ->whereHas('categories', fn ($q) => $q->where('ecommerce_product_categories.id', $category->id))
            ->with(['brand'])
            ->paginate((int) request('per_page', 15));

        $detail = (new CategoryDetailResource($category))->toArray(request());
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
