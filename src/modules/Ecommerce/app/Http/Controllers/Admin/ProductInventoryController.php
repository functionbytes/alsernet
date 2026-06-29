<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Product;

class ProductInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->select(['id', 'name', 'sku', 'quantity', 'with_storehouse_management', 'status'])
            ->where('is_variation', false)
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"))
            ->when($request->input('stock'), function ($q, $stock) {
                match ($stock) {
                    'low' => $q->where('quantity', '<=', 5)->where('quantity', '>', 0),
                    'out' => $q->where('quantity', 0),
                    'ok' => $q->where('quantity', '>', 5),
                    default => null,
                };
            })
            ->orderBy('quantity')
            ->paginate(50);

        return view('ecommerce::products.inventory', compact('products'));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product->update([
            'quantity' => $request->integer('quantity'),
            'with_storehouse_management' => true,
        ]);

        return response()->json(['success' => true, 'quantity' => $product->quantity]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:ecommerce_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items') as $item) {
            Product::query()->where('id', $item['id'])->update([
                'quantity' => $item['quantity'],
                'with_storehouse_management' => true,
            ]);
        }

        return response()->json(['success' => true, 'updated' => count($request->input('items'))]);
    }
}
