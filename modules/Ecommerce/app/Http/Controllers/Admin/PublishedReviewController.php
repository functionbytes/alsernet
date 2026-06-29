<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Models\Review;

class PublishedReviewController extends Controller
{
    public function __invoke(Review $review): JsonResponse
    {
        $newStatus = $review->status === 'approved' ? 'pending' : 'approved';

        $review->update(['status' => $newStatus]);

        return response()->json(['approved' => $review->status === 'approved']);
    }
}
