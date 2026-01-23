<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Reports;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Csat\CsatSurveyResponse;

class CsatReportController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $period = (int) $request->input('period', 30);
        $startDate = now()->subDays($period);

        // Total surveys sent and completed
        $totalSent = CsatSurveyResponse::where('account_id', $accountId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalCompleted = CsatSurveyResponse::where('account_id', $accountId)
            ->completed()
            ->where('created_at', '>=', $startDate)
            ->count();

        $responseRate = $totalSent > 0 ? round(($totalCompleted / $totalSent) * 100, 1) : 0;

        // Rating distribution
        $ratingDistribution = CsatSurveyResponse::where('account_id', $accountId)
            ->completed()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->keyBy('rating');

        // Ensure all ratings 1-5 are present
        $ratings = collect([5, 4, 3, 2, 1])->mapWithKeys(function ($rating) use ($ratingDistribution) {
            return [$rating => $ratingDistribution->get($rating)?->count ?? 0];
        });

        // Calculate average rating
        $totalRatings = $ratings->sum();
        $weightedSum = $ratings->map(fn ($count, $rating) => $rating * $count)->sum();
        $averageRating = $totalRatings > 0 ? round($weightedSum / $totalRatings, 1) : 0;

        // Satisfaction rate (4-5 stars)
        $satisfied = ($ratings->get(5, 0) + $ratings->get(4, 0));
        $satisfactionRate = $totalCompleted > 0 ? round(($satisfied / $totalCompleted) * 100, 1) : 0;

        // Agent performance
        $agentPerformance = CsatSurveyResponse::where('helpdesk_csat_survey_responses.account_id', $accountId)
            ->completed()
            ->where('helpdesk_csat_survey_responses.created_at', '>=', $startDate)
            ->join('users', 'helpdesk_csat_survey_responses.assigned_agent_id', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as total_responses,
                AVG(helpdesk_csat_survey_responses.rating) as avg_rating,
                SUM(CASE WHEN helpdesk_csat_survey_responses.rating >= 4 THEN 1 ELSE 0 END) as satisfied_count
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('avg_rating')
            ->get()
            ->map(function ($agent) {
                $agent->avg_rating = round($agent->avg_rating, 1);
                $agent->satisfaction_rate = $agent->total_responses > 0
                    ? round(($agent->satisfied_count / $agent->total_responses) * 100, 1)
                    : 0;

                return $agent;
            });

        // Recent responses with feedback
        $recentFeedback = CsatSurveyResponse::where('account_id', $accountId)
            ->completed()
            ->whereNotNull('feedback')
            ->where('created_at', '>=', $startDate)
            ->with(['contact', 'conversation', 'assignedAgent'])
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        // Daily trend (last 7 days)
        $dailyTrend = CsatSurveyResponse::where('account_id', $accountId)
            ->completed()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(submitted_at) as date, AVG(rating) as avg_rating, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('helpdeskchat::admin.reports.csat.index', compact(
            'period',
            'totalSent',
            'totalCompleted',
            'responseRate',
            'averageRating',
            'satisfactionRate',
            'ratings',
            'agentPerformance',
            'recentFeedback',
            'dailyTrend'
        ));
    }
}
