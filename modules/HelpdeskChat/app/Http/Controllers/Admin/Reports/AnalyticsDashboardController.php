<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Services\Analytics\ConversationAnalyticsService;

class AnalyticsDashboardController extends Controller
{
    public function __construct(
        protected ConversationAnalyticsService $analyticsService
    ) {}

    /**
     * Display analytics dashboard.
     */
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // Parse date range from request or use defaults
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        // Get analytics data
        $overview = $this->analyticsService->getOverviewMetrics($accountId, $startDate, $endDate);
        $byDay = $this->analyticsService->getConversationsByDay($accountId, $startDate, $endDate);
        $byChannel = $this->analyticsService->getConversationsByChannel($accountId, $startDate, $endDate);
        $byStatus = $this->analyticsService->getConversationsByStatus($accountId, $startDate, $endDate);
        $topAgents = $this->analyticsService->getTopAgents($accountId, $startDate, $endDate, 5);
        $busiestHours = $this->analyticsService->getBusiestHours($accountId, $startDate, $endDate);

        return view('helpdeskchat::admin.reports.analytics.index', compact(
            'overview',
            'byDay',
            'byChannel',
            'byStatus',
            'topAgents',
            'busiestHours',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        $accountId = auth()->user()->account_id;

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $data = $this->analyticsService->exportData($accountId, $startDate, $endDate);

        return response()->json($data);
    }
}
