<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\Ticket;

class RatingsController extends Controller
{
    /**
     * Return ticket rating statistics.
     *
     * GET /api/helpdesk/ratings/summary
     */
    public function summary(): JsonResponse
    {
        $data = Ticket::whereNotNull('rated_at')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_rated')
            ->first();

        $distribution = Ticket::whereNotNull('rated_at')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        return response()->json([
            'avg_rating' => round((float) $data->avg_rating, 2),
            'total_rated' => (int) $data->total_rated,
            'distribution' => $distribution,
        ]);
    }
}
