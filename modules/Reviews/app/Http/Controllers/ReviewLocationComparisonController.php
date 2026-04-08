<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Services\LocationComparisonService;
use Modules\Reviews\Services\ResponseTimeAnalyticsService;

class ReviewLocationComparisonController extends Controller
{
    public function __construct(
        private readonly LocationComparisonService $comparisonService,
        private readonly ResponseTimeAnalyticsService $responseTimeService
    ) {
        $this->middleware('can:reviews.reviews.view');
    }

    public function index(): View
    {
        $locations = ReviewGoogleLocation::query()
            ->whereHas('connection', fn ($q) => $q->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return view('reviews::locations.comparison', compact('locations'));
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_ids' => ['required', 'array', 'min:2', 'max:10'],
            'location_ids.*' => ['required', 'integer', 'exists:review_google_locations,id'],
            'days' => ['nullable', 'integer', 'in:7,30,90'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $locationIds = array_map('intval', $validated['location_ids']);

        $comparison = $this->comparisonService->compare($locationIds, $days);
        $summary = $this->comparisonService->getPerformanceSummary($locationIds, $days);

        return response()->json([
            'success' => true,
            'data' => [
                'comparison' => array_values($comparison),
                'summary' => $summary,
                'days' => $days,
            ],
        ]);
    }

    public function rankings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $rankings = $this->comparisonService->getRankings(auth()->id(), $days);

        return response()->json([
            'success' => true,
            'data' => $rankings,
        ]);
    }

    public function responseTimeData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:review_google_locations,id'],
            'days' => ['nullable', 'integer', 'in:7,30,90'],
        ]);

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        $days = (int) ($validated['days'] ?? 30);

        $stats = $this->responseTimeService->getResponseTimeStats($locationId, $days);
        $byUser = $this->responseTimeService->getResponseTimeByUser($locationId, $days);
        $byDow = $this->responseTimeService->getResponseTimeByDayOfWeek($locationId);

        // Add human-readable formatted values
        $stats['avg_formatted'] = ResponseTimeAnalyticsService::formatHours($stats['avg_response_time_hours']);
        $stats['median_formatted'] = ResponseTimeAnalyticsService::formatHours($stats['median_response_time_hours']);
        $stats['fastest_formatted'] = ResponseTimeAnalyticsService::formatHours($stats['fastest_response_hours']);
        $stats['slowest_formatted'] = ResponseTimeAnalyticsService::formatHours($stats['slowest_response_hours']);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'by_user' => $byUser,
                'by_day_of_week' => $byDow,
            ],
        ]);
    }
}
