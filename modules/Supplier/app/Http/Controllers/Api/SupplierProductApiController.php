<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Http\Requests\Api\UpdateProductVisibilityRequest;
use Modules\Supplier\Http\Resources\ProductResource;
use Modules\Supplier\Models\Product\Product;

class SupplierProductApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['supplier:id,uid,label', 'category:id,name'])
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->has('available'), fn ($q) => $q->where('available', $request->boolean('available')))
            ->when($request->has('web_published'), fn ($q) => $q->where('web_published', $request->boolean('web_published')))
            ->when($request->sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when($request->sort !== 'name', fn ($q) => $q->latest())
            ->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    public function show(int $id): ProductResource
    {
        $product = Product::with(['attributes', 'translations', 'supplier', 'category'])
            ->findOrFail($id);

        return new ProductResource($product);
    }

    public function update(UpdateProductVisibilityRequest $request, int $id): ProductResource
    {
        $product = Product::findOrFail($id);

        $product->update($request->validated());

        return new ProductResource($product->load(['supplier:id,uid,label', 'category:id,name']));
    }
}
