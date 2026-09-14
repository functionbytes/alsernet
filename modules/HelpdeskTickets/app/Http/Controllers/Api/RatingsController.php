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

        // Desglose de las razones de insatisfacción (el *por qué* del CSAT bajo).
        $reasonLabels = (array) config('helpdesktickets.csat_reasons', []);
        $reasons = Ticket::whereNotNull('rated_at')
            ->whereNotNull('rating_reason')
            ->selectRaw('rating_reason, COUNT(*) as count')
            ->groupBy('rating_reason')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'reason' => $row->rating_reason,
                'label' => $reasonLabels[$row->rating_reason] ?? $row->rating_reason,
                'count' => (int) $row->count,
            ]);

        return ApiResponse::success([
            'avgRating' => round((float) $data->avg_rating, 2),
            'totalRated' => (int) $data->total_rated,
            'distribution' => $distribution,
            'dissatisfactionReasons' => $reasons,
        ]);
    }
}
