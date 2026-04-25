<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Cart;

class CartApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Cart::query()
            ->with('product')
            ->when($request->user('sanctum'), fn ($q) => $q->where('customer_id', $request->user('sanctum')->id))
            ->get();

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:ecommerce_products,id'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $customer = $request->user('sanctum');
        $existing = Cart::query()
            ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            $existing->update(['qty' => $existing->qty + $validated['qty']]);
        } else {
            Cart::query()->create([
                'customer_id' => $customer?->id,
                'session_id' => $customer ? null : $request->session()->getId(),
                'product_id' => $validated['product_id'],
                'qty' => $validated['qty'],
            ]);
        }

        return response()->json(['message' => 'Producto agregado al carrito']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $cartItem = Cart::query()->findOrFail($id);
        $cartItem->update(['qty' => max(1, (int) $request->input('qty', 1))]);

        return response()->json(['message' => 'Carrito actualizado']);
    }

    public function destroy(int $id): JsonResponse
    {
        Cart::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'Producto removido']);
    }
}
