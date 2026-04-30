<?php

namespace Modules\Chat\Services\Analytics;

use Carbon\Carbon;
use Modules\Chat\Models\Csat\CsatSurveyResponse;

class CsatAnalyticsService
{
    public function __construct(
        protected int $accountId
    ) {}

    /**
     * Get comprehensive CSAT metrics for a date range.
     */
    public function getMetrics(int $period): array
    {
        $startDate = now()->subDays($period);

        return [
            'survey_stats' => $this->getSurveyStats($startDate),
            'rating_distribution' => $this->getRatingDistribution($startDate),
            'agent_performance' => $this->getAgentPerformance($startDate),
            'recent_feedback' => $this->getRecentFeedback($startDate),
            'daily_trend' => $this->getDailyTrend(),
        ];
    }

    /**
     * Get survey statistics (sent, completed, response rate).
     */
    protected function getSurveyStats(Carbon $startDate): array
    {
        $agg = CsatSurveyResponse::where('account_id', $this->accountId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('COUNT(*) as total_sent')
            ->selectRaw('SUM(CASE WHEN submitted_at IS NOT NULL THEN 1 ELSE 0 END) as total_completed')
            ->first();

        $totalSent = (int) ($agg->total_sent ?? 0);
        $totalCompleted = (int) ($agg->total_completed ?? 0);
        $responseRate = $totalSent > 0 ? round(($totalCompleted / $totalSent) * 100, 1) : 0;

        return [
            'total_sent' => $totalSent,
            'total_completed' => $totalCompleted,
            'response_rate' => $responseRate,
        ];
    }

    /**
     * Get rating distribution (1-5 stars).
     */
    protected function getRatingDistribution(Carbon $startDate): array
    {
        $distribution = CsatSurveyResponse::where('account_id', $this->accountId)
            ->completed()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->keyBy('rating');

        $ratings = collect([5, 4, 3, 2, 1])->mapWithKeys(function ($rating) use ($distribution) {
            return [$rating => $distribution->get($rating)?->count ?? 0];
        });

        $totalRatings = $ratings->sum();
        $weightedSum = $ratings->map(fn ($count, $rating) => $rating * $count)->sum();
        $averageRating = $totalRatings > 0 ? round($weightedSum / $totalRatings, 1) : 0;

        $satisfied = ($ratings->get(5, 0) + $ratings->get(4, 0));
        $satisfactionRate = $totalRatings > 0 ? round(($satisfied / $totalRatings) * 100, 1) : 0;

        return [
            'ratings' => $ratings->toArray(),
            'average_rating' => $averageRating,
            'satisfaction_rate' => $satisfactionRate,
            'total_ratings' => $totalRatings,
        ];
    }

    /**
     * Get agent performance metrics.
     */
    protected function getAgentPerformance(Carbon $startDate): array
    {
        return CsatSurveyResponse::where('chat_csat_survey_responses.account_id', $this->accountId)
            ->completed()
            ->where('chat_csat_survey_responses.created_at', '>=', $startDate)
            ->join('users', 'chat_csat_survey_responses.assigned_agent_id', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as total_responses,
                AVG(chat_csat_survey_responses.rating) as avg_rating,
                SUM(CASE WHEN chat_csat_survey_responses.rating >= 4 THEN 1 ELSE 0 END) as satisfied_count
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
            })
            ->toArray();
    }

    /**
     * Get recent feedback with details.
     */
    protected function getRecentFeedback(Carbon $startDate, int $limit = 10): array
    {
        return CsatSurveyResponse::where('account_id', $this->accountId)
            ->completed()
            ->whereNotNull('feedback')
            ->where('created_at', '>=', $startDate)
            ->with(['customer', 'conversation', 'assignedAgent'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get daily trend for the last 7 days.
     */
    protected function getDailyTrend(): array
    {
        return CsatSurveyResponse::where('account_id', $this->accountId)
            ->completed()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(submitted_at) as date, AVG(rating) as avg_rating, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
