<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Models\Ticket;

class RatingsController extends Controller
{
    public function summary(): JsonResponse
    {
        $this->authorize('helpdesk.metrics.view');

        $data = Ticket::whereNotNull('rated_at')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_rated')
            ->first();

        $distribution = Ticket::whereNotNull('rated_at')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        return ApiResponse::success([
            'avgRating' => round((float) $data->avg_rating, 2),
            'totalRated' => (int) $data->total_rated,
            'distribution' => $distribution,
        ]);
    }
}
