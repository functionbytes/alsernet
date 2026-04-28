<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Http\Requests\Api\V1\Review\StoreReviewRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Review\UpdateReviewRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\ReviewResource;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Review;

class ReviewController extends BaseApiController
{
    public function index(Product $product): JsonResponse
    {
        $reviews = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'approved')
            ->latest()
            ->paginate((int) request('per_page', 15));

        return $this->paginated($reviews, ReviewResource::class);
    }

    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        $customer = $request->user();

        // Verified buyer check
        $hasPurchased = Order::query()
            ->where('customer_id', $customer->id)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->where('status', 'completed')
            ->exists();

        if (! $hasPurchased) {
            return $this->errorResponse(
                'Solo compradores verificados pueden dejar reseñas.',
                'NOT_VERIFIED_BUYER',
                403
            );
        }

        $review = Review::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'star' => (int) $request->input('star'),
            'comment' => $request->input('comment'),
            'is_verified_buyer' => true,
            'status' => 'pending',
        ]);

        return $this->created(new ReviewResource($review));
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        // Allow edits only within 7 days of creation
        if ($review->created_at->diffInDays(now()) > 7) {
            return $this->errorResponse('Ya no puedes editar esta reseña.', 'REVIEW_LOCKED', 422);
        }

        $review->update($request->validated());

        return $this->ok(new ReviewResource($review->fresh()));
    }

    public function destroy(Review $review): JsonResponse
    {
        if ($review->customer_id !== auth()->id()) {
            return $this->errorResponse('No autorizado.', 'FORBIDDEN', 403);
        }

        $review->delete();

        return $this->noContent('Reseña eliminada.');
    }
}
