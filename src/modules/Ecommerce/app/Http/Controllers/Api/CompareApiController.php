<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\ProductResource;
use Modules\Ecommerce\Models\Product;

class CompareApiController extends Controller
{
    private const MAX_COMPARE_ITEMS = 4;

    private const SESSION_KEY = 'ecommerce_compare_ids';

    public function index(Request $request): JsonResponse
    {
        $ids = $request->input('compare_ids')
            ?? $request->session()->get(self::SESSION_KEY, []);

        if (empty($ids)) {
            return response()->json(['data' => [], 'count' => 0]);
        }

        $products = Product::query()
            ->whereIn('id', $ids)
            ->with(['brand:id,name', 'categories:id,name'])
            ->get();

        return response()->json([
            'data' => ProductResource::collection($products),
            'count' => $products->count(),
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:ecommerce_products,id'],
        ]);

        $productId = (int) $request->input('product_id');
        $ids = $request->session()->get(self::SESSION_KEY, []);

        if (in_array($productId, $ids)) {
            return response()->json([
                'success' => true,
                'count' => count($ids),
                'productId' => $productId,
            ]);
        }

        if (count($ids) >= self::MAX_COMPARE_ITEMS) {
            return response()->json([
                'message' => 'Solo puedes comparar hasta '.self::MAX_COMPARE_ITEMS.' productos a la vez.',
            ], 422);
        }

        $ids[] = $productId;
        $request->session()->put(self::SESSION_KEY, $ids);

        return response()->json([
            'success' => true,
            'count' => count($ids),
            'productId' => $productId,
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $productId = (int) $request->input('product_id');
        $ids = $request->session()->get(self::SESSION_KEY, []);

        $ids = array_values(array_filter($ids, fn ($id) => $id !== $productId));
        $request->session()->put(self::SESSION_KEY, $ids);

        return response()->json([
            'success' => true,
            'count' => count($ids),
        ]);
    }
}
