<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Reviews\Http\Resources\DashboardStatsResource;
use Modules\Reviews\Services\ReviewDashboardService;

class ReviewDashboardController extends Controller
{
    public function __construct(
        private readonly ReviewDashboardService $dashboardService
    ) {}

    public function index(): View
    {
        return view('reviews::dashboard.index');
    }

    public function data(): JsonResponse
    {
        $data = [
            'kpis' => $this->dashboardService->getKpiMetrics(),
            'rating_trends' => $this->dashboardService->getRatingTrends(12),
            'location_stats' => $this->dashboardService->getLocationStats(),
            'reviews_by_day' => $this->dashboardService->getReviewsByDay(30),
            'rating_distribution' => $this->dashboardService->getRatingDistribution(),
        ];

        return response()->json(new DashboardStatsResource($data));
    }
}
