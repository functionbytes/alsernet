<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Ecommerce\Http\Requests\StoreRestockAlertRequest;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductRestockAlert;

class RestockAlertController extends Controller
{
    public function store(StoreRestockAlertRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $isOutOfStock = $product->with_storehouse_management
            && $product->quantity <= 0
            && ! $product->allow_checkout_when_out_of_stock;

        if (! $isOutOfStock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este producto ya está disponible.',
                ], 422);
            }

            return back()->with('warning', 'Este producto ya está disponible.');
        }

        ProductRestockAlert::firstOrCreate(
            ['product_id' => $product->id, 'email' => $request->input('email')],
            ['customer_id' => auth('ecommerce')->id()]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Te avisaremos cuando esté disponible.',
            ]);
        }

        return back()->with('success', 'Te avisaremos cuando el producto esté disponible.');
    }
}
