<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Ecommerce\Models\Review;

class ReviewService
{
    public function getReviews(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Review::query()
            ->with(['customer', 'product'])
            ->when($filters['product_id'] ?? null, function ($q, $productId) {
                $q->where('product_id', $productId);
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function getAverageRating(int $productId): float
    {
        return (float) Review::query()
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->avg('star');
    }
}
