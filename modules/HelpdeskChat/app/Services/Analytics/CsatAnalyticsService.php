<?php

namespace Modules\HelpdeskChat\Services\Analytics;

use App\Models\CsatSurveyResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CsatAnalyticsService
{
    /**
     * Get CSAT metrics for a given period.
     */
    public function getMetrics(int $accountId, ?Carbon $startDate = null, ?Carbon $endDate = null, array $filters = []): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $query = CsatSurveyResponse::forAccount($accountId)
            ->completed()
            ->whereBetween('submitted_at', [$startDate, $endDate]);

        // Apply filters
        if (! empty($filters['agent_id'])) {
            $query->where('assigned_agent_id', $filters['agent_id']);
        }

        $responses = $query->get();

        if ($responses->isEmpty()) {
            return $this->getEmptyMetrics();
        }

        // Calculate metrics
        $totalResponses = $responses->count();
        $averageRating = round($responses->avg('rating'), 2);
        $responseRate = $this->calculateResponseRate($accountId, $startDate, $endDate);

        // Rating distribution
        $distribution = $responses->groupBy('rating')->map->count();

        // Satisfaction metrics
        $satisfied = $responses->whereIn('rating', [4, 5])->count();
        $neutral = $responses->where('rating', 3)->count();
        $dissatisfied = $responses->whereIn('rating', [1, 2])->count();

        $satisfactionScore = $totalResponses > 0 ? round(($satisfied / $totalResponses) * 100, 2) : 0;

        // NPS calculation (treating 4-5 as promoters, 3 as passives, 1-2 as detractors)
        $promoters = $responses->whereIn('rating', [4, 5])->count();
        $detractors = $responses->whereIn('rating', [1, 2])->count();
        $nps = $totalResponses > 0 ? round((($promoters - $detractors) / $totalResponses) * 100, 2) : 0;

        return [
            'total_responses' => $totalResponses,
            'response_rate' => $responseRate,
            'average_rating' => $averageRating,
            'satisfaction_score' => $satisfactionScore,
            'nps' => $nps,
            'distribution' => [
                '1' => $distribution->get(1, 0),
                '2' => $distribution->get(2, 0),
                '3' => $distribution->get(3, 0),
                '4' => $distribution->get(4, 0),
                '5' => $distribution->get(5, 0),
            ],
            'satisfied' => $satisfied,
            'neutral' => $neutral,
            'dissatisfied' => $dissatisfied,
            'with_feedback' => $responses->whereNotNull('feedback')->count(),
        ];
    }

    /**
     * Calculate response rate.
     */
    protected function calculateResponseRate(int $accountId, Carbon $startDate, Carbon $endDate): float
    {
        // Total surveys sent
        $totalSent = CsatSurveyResponse::forAccount($accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalSent === 0) {
            return 0;
        }

        // Total completed
        $totalCompleted = CsatSurveyResponse::forAccount($accountId)
            ->completed()
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->count();

        return round(($totalCompleted / $totalSent) * 100, 2);
    }

    /**
     * Get trends over time.
     */
    public function getTrends(int $accountId, Carbon $startDate, Carbon $endDate, string $groupBy = 'day'): array
    {
        $dateFormat = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $trends = CsatSurveyResponse::forAccount($accountId)
            ->completed()
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(submitted_at, '{$dateFormat}') as period"),
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('COUNT(*) as total_responses'),
                DB::raw('SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as satisfied'),
                DB::raw('SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as dissatisfied')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $trends->map(function ($trend) {
            $satisfactionRate = $trend->total_responses > 0
                ? round(($trend->satisfied / $trend->total_responses) * 100, 2)
                : 0;

            return [
                'period' => $trend->period,
                'avg_rating' => round($trend->avg_rating, 2),
                'total_responses' => $trend->total_responses,
                'satisfaction_rate' => $satisfactionRate,
                'satisfied' => $trend->satisfied,
                'dissatisfied' => $trend->dissatisfied,
            ];
        })->toArray();
    }

    /**
     * Get agent performance.
     */
    public function getAgentPerformance(int $accountId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        $agentStats = CsatSurveyResponse::forAccount($accountId)
            ->completed()
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->whereNotNull('assigned_agent_id')
            ->select(
                'assigned_agent_id',
                DB::raw('COUNT(*) as total_responses'),
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as satisfied'),
                DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as neutral'),
                DB::raw('SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as dissatisfied')
            )
            ->groupBy('assigned_agent_id')
            ->with('assignedAgent:id,name,email')
            ->get();

        return $agentStats->map(function ($stat) {
            $satisfactionRate = $stat->total_responses > 0
                ? round(($stat->satisfied / $stat->total_responses) * 100, 2)
                : 0;

            return [
                'agent_id' => $stat->assigned_agent_id,
                'agent_name' => $stat->assignedAgent->name ?? 'Unknown',
                'agent_email' => $stat->assignedAgent->email ?? '',
                'total_responses' => $stat->total_responses,
                'avg_rating' => round($stat->avg_rating, 2),
                'satisfaction_rate' => $satisfactionRate,
                'satisfied' => $stat->satisfied,
                'neutral' => $stat->neutral,
                'dissatisfied' => $stat->dissatisfied,
            ];
        })->sortByDesc('avg_rating')->values()->toArray();
    }

    /**
     * Get recent feedback.
     */
    public function getRecentFeedback(int $accountId, int $limit = 10, ?int $minRating = null, ?int $maxRating = null): array
    {
        $query = CsatSurveyResponse::forAccount($accountId)
            ->completed()
            ->whereNotNull('feedback')
            ->with(['contact:id,name,email', 'assignedAgent:id,name', 'conversation:id,status'])
            ->orderBy('submitted_at', 'desc')
            ->limit($limit);

        if ($minRating !== null) {
            $query->where('rating', '>=', $minRating);
        }

        if ($maxRating !== null) {
            $query->where('rating', '<=', $maxRating);
        }

        return $query->get()->map(function ($response) {
            return [
                'id' => $response->id,
                'rating' => $response->rating,
                'rating_emoji' => $response->rating_emoji,
                'rating_label' => $response->rating_label,
                'feedback' => $response->feedback,
                'submitted_at' => $response->submitted_at->format('Y-m-d H:i:s'),
                'contact_name' => $response->contact->name ?? 'Unknown',
                'contact_email' => $response->contact->email ?? '',
                'agent_name' => $response->assignedAgent->name ?? 'Unassigned',
                'conversation_id' => $response->conversation_id,
            ];
        })->toArray();
    }

    /**
     * Get empty metrics structure.
     */
    protected function getEmptyMetrics(): array
    {
        return [
            'total_responses' => 0,
            'response_rate' => 0,
            'average_rating' => 0,
            'satisfaction_score' => 0,
            'nps' => 0,
            'distribution' => [
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ],
            'satisfied' => 0,
            'neutral' => 0,
            'dissatisfied' => 0,
            'with_feedback' => 0,
        ];
    }
}
