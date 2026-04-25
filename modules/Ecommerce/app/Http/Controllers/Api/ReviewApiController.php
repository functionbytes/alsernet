<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\ReviewResource;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Review;

class ReviewApiController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        $reviews = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json(ReviewResource::collection($reviews));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'star' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:1000'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
        ]);

        $review = Review::query()->create([
            'product_id' => $product->id,
            'customer_id' => $request->user('sanctum')?->id,
            ...$validated,
            'status' => 'pending',
        ]);

        return response()->json(new ReviewResource($review), 201);
    }
}
