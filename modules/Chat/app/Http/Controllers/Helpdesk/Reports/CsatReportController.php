<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Reports;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Analytics\CsatAnalyticsService;

class CsatReportController extends Controller
{
    public function index(Request $request): View
    {
        $accountId = auth()->user()->account_id;
        abort_unless($accountId, 403, 'User must belong to an account');

        $period = (int) $request->input('period', 30);
        $csatService = new CsatAnalyticsService($accountId);
        $data = $csatService->getMetrics($period);

        return view('Chat::chats.reports.csat.index', [
            'period' => $period,
            'totalSent' => $data['survey_stats']['total_sent'],
            'totalCompleted' => $data['survey_stats']['total_completed'],
            'responseRate' => $data['survey_stats']['response_rate'],
            'averageRating' => $data['rating_distribution']['average_rating'],
            'satisfactionRate' => $data['rating_distribution']['satisfaction_rate'],
            'ratings' => $data['rating_distribution']['ratings'],
            'agentPerformance' => $data['agent_performance'],
            'recentFeedback' => $data['recent_feedback'],
            'dailyTrend' => $data['daily_trend'],
        ]);
    }
}
