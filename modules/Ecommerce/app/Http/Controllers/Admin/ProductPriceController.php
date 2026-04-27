<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Product;

class ProductPriceController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->select(['id', 'name', 'sku', 'price', 'sale_price', 'start_date', 'end_date', 'status'])
            ->where('is_variation', false)
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(50);

        return view('ecommerce::products.prices', compact('products'));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $product->update($request->only(['price', 'sale_price', 'start_date', 'end_date']));

        return response()->json([
            'success' => true,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:ecommerce_products,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.sale_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            Product::query()->where('id', $item['id'])->update([
                'price' => $item['price'],
                'sale_price' => $item['sale_price'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'updated' => count($request->input('items'))]);
    }
}
