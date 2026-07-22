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
        abort_if(! helpdesk_analytics_enabled(), 404);

        return view('helpdeskanalytics::dashboard.index');
    }

    public function data(AnalyticsRangeRequest $request): JsonResponse
    {
        if (! helpdesk_analytics_enabled()) {
            return response()->json([
                'success' => true,
                'available' => false,
                'message' => 'La integración de Analytics está deshabilitada.',
            ]);
        }

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        // Aislamiento por bandeja: sin helpdesk.manage, los agregados se
        // limitan a las bandejas asignadas al usuario (AgentInboxCapacity).
        $user = $request->user();

        return response()->json([
            'success' => true,
            'available' => true,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'overview' => $this->analytics->overview($from, $to, $user),
            'channels' => $this->analytics->channelDistribution($from, $to, $user),
            'agents' => $this->analytics->agentPerformance($from, $to, $user),
            'trends' => $this->analytics->trends($from, $to, $user),
            'heatmap' => $this->analytics->heatmap($from, $to, $user),
            'customers' => $this->analytics->customerSegments($from, $to, $user),
            'tickets' => $this->analytics->ticketMetrics($from, $to, $user),
        ]);
    }
}
