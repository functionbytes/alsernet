<?php

namespace Modules\HelpdeskAnalytics\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\HelpdeskAnalytics\Http\Requests\Managers\AnalyticsRangeRequest;
use Modules\HelpdeskAnalytics\Services\AnalyticsAggregatorService;

/**
 * Cross-channel analytics dashboard for the Helpdesk inbox. Read-only: a single
 * JSON feed backs the dashboard, every aggregate computed without N+1 and cached.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsAggregatorService $analytics,
    ) {
        $this->middleware('can:helpdeskanalytics.view');
    }

    public function index(): View
    {
        return view('helpdeskanalytics::dashboard.index');
    }

    public function data(AnalyticsRangeRequest $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        return response()->json([
            'success' => true,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'overview' => $this->analytics->overview($from, $to),
            'channels' => $this->analytics->channelDistribution($from, $to),
            'agents' => $this->analytics->agentPerformance($from, $to),
            'trends' => $this->analytics->trends($from, $to),
            'heatmap' => $this->analytics->heatmap($from, $to),
            'customers' => $this->analytics->customerSegments($from, $to),
        ]);
    }
}
