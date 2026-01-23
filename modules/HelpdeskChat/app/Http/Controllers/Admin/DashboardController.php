<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Services\Analytics\DashboardMetricsService;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $accountId = $user->account_id;

        // Get comprehensive metrics
        $metricsService = new DashboardMetricsService($accountId);
        $metrics = $metricsService->getAllMetrics();

        // Get period comparisons (month-over-month)
        $comparisons = $metricsService->getPeriodComparisons();

        // Get performance alerts
        $alerts = $metricsService->getPerformanceAlerts();

        // Get recent conversation
        $recentConversations = Conversation::whereHas('inbox', function ($query) use ($accountId) {
            $query->where('account_id', $accountId);
        })
            ->with(['contact', 'inbox', 'assignee'])
            ->latest('last_activity_at')
            ->limit(10)
            ->get();

        // Get inboxes
        $inboxes = Inbox::where('account_id', $accountId)
            ->with('channel')
            ->get();

        return view('helpdeskchat::admin.dashboard', compact('metrics', 'comparisons', 'alerts', 'recentConversations', 'inboxes'));
    }

    /**
     * Get dashboard metrics as JSON (for real-time updates).
     */
    public function metrics(Request $request)
    {
        $metricsService = new DashboardMetricsService($request->user()->account_id);

        return response()->json($metricsService->getAllMetrics());
    }

    /**
     * Get specific metric widget.
     */
    public function widget(Request $request, string $widget)
    {
        $metricsService = new DashboardMetricsService($request->user()->account_id);

        $data = match ($widget) {
            'overview' => $metricsService->getOverviewMetrics(),
            'conversation' => $metricsService->getConversationMetrics(),
            'agents' => $metricsService->getAgentMetrics(),
            'response_times' => $metricsService->getResponseTimeMetrics(),
            'trending' => $metricsService->getTrendingMetrics(),
            default => ['error' => 'Invalid widget'],
        };

        return response()->json($data);
    }
}
