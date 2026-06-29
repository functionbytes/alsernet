<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\OrderResource;
use Modules\Ecommerce\Models\Order;

class OrderApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->when($request->user('sanctum'), fn ($q) => $q->where('customer_id', $request->user('sanctum')->id))
            ->with('products')
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json(new OrderResource($order->load('products', 'histories')));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:pending,processing,completed,cancelled'],
        ]);

        $order = Order::query()->create($validated);

        return response()->json(new OrderResource($order), 201);
    }
}
