<?php

namespace Modules\HelpdeskChat\Services\Analytics;

use App\Models\AgentPerformanceSnapshot;
use App\Models\CsatSurveyResponse;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class AgentPerformanceService
{
    protected int $accountId;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * Get comprehensive agent metrics for a specific period.
     */
    public function getAgentMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        // Try to use snapshots for historical data (older than today)
        if ($end->isYesterday() || $end->isPast()) {
            return $this->getMetricsFromSnapshots($userId, $start, $end);
        }

        // Calculate real-time for current/recent data
        return $this->calculateRealTimeMetrics($userId, $start, $end);
    }

    /**
     * Calculate real-time metrics for an agent.
     */
    public function calculateRealTimeMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        return [
            'agent' => $this->getAgentInfo($userId),
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $start->diffInDays($end) + 1,
            ],
            'conversation' => $this->getConversationMetrics($userId, $start, $end),
            'messages' => $this->getMessageMetrics($userId, $start, $end),
            'response_times' => $this->getResponseTimeMetrics($userId, $start, $end),
            'csat' => $this->getCsatMetrics($userId, $start, $end),
            'sla' => $this->getSlaMetrics($userId, $start, $end),
        ];
    }

    /**
     * Get metrics from pre-calculated snapshots.
     */
    protected function getMetricsFromSnapshots(int $userId, Carbon $start, Carbon $end): array
    {
        $snapshots = AgentPerformanceSnapshot::forAgent($userId)
            ->forDateRange($start, $end)
            ->daily()
            ->orderBy('snapshot_date')
            ->get();

        if ($snapshots->isEmpty()) {
            // Fallback to real-time calculation if no snapshots exist
            return $this->calculateRealTimeMetrics($userId, $start, $end);
        }

        // Aggregate snapshot data
        return [
            'agent' => $this->getAgentInfo($userId),
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $snapshots->count(),
            ],
            'conversation' => [
                'assigned' => $snapshots->sum(fn ($s) => $s->getMetric('conversations_assigned', 0)),
                'resolved' => $snapshots->sum(fn ($s) => $s->getMetric('conversations_resolved', 0)),
                'pending' => $snapshots->sum(fn ($s) => $s->getMetric('conversations_pending', 0)),
            ],
            'messages' => [
                'sent' => $snapshots->sum(fn ($s) => $s->getMetric('messages_sent', 0)),
            ],
            'response_times' => [
                'first_response_avg' => round($snapshots->avg(fn ($s) => $s->getMetric('first_response_time_avg', 0)), 2),
                'resolution_avg' => round($snapshots->avg(fn ($s) => $s->getMetric('resolution_time_avg', 0)), 2),
            ],
            'csat' => [
                'avg_rating' => round($snapshots->avg(fn ($s) => $s->getMetric('csat_avg_rating', 0)), 2),
                'total_responses' => $snapshots->sum(fn ($s) => $s->getMetric('csat_responses', 0)),
            ],
            'sla' => [
                'breaches' => $snapshots->sum(fn ($s) => $s->getMetric('sla_breaches', 0)),
            ],
        ];
    }

    /**
     * Get conversation-related metrics.
     */
    protected function getConversationMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $query = Conversation::where('assignee_id', $userId)
            ->whereHas('inbox', fn ($q) => $q->where('account_id', $this->accountId))
            ->whereBetween('created_at', [$start, $end]);

        $total = (clone $query)->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $open = (clone $query)->where('status', 'open')->count();

        return [
            'assigned' => $total,
            'resolved' => $resolved,
            'pending' => $pending,
            'open' => $open,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get message-related metrics.
     */
    protected function getMessageMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $sent = Message::where('sender_type', User::class)
            ->where('sender_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('conversation.inbox', fn ($q) => $q->where('account_id', $this->accountId))
            ->count();

        return [
            'sent' => $sent,
            'avg_per_day' => $start->diffInDays($end) > 0
                ? round($sent / ($start->diffInDays($end) + 1), 2)
                : $sent,
        ];
    }

    /**
     * Get response time metrics.
     */
    protected function getResponseTimeMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        // First response time
        $firstResponseData = Conversation::where('assignee_id', $userId)
            ->whereHas('inbox', fn ($q) => $q->where('account_id', $this->accountId))
            ->whereNotNull('first_response_at')
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_time'))
            ->first();

        // Resolution time
        $resolutionData = Conversation::where('assignee_id', $userId)
            ->whereHas('inbox', fn ($q) => $q->where('account_id', $this->accountId))
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time'))
            ->first();

        return [
            'first_response_avg' => round($firstResponseData->avg_time ?? 0, 2),
            'resolution_avg' => round($resolutionData->avg_time ?? 0, 2),
        ];
    }

    /**
     * Get CSAT (Customer Satisfaction) metrics.
     */
    protected function getCsatMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $responses = CsatSurveyResponse::whereHas('conversation', function ($q) use ($userId) {
            $q->where('assignee_id', $userId)
                ->whereHas('inbox', fn ($query) => $query->where('account_id', $this->accountId));
        })
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalResponses = $responses->count();
        $avgRating = $responses->avg('rating');

        return [
            'avg_rating' => $avgRating ? round($avgRating, 2) : 0,
            'total_responses' => $totalResponses,
            'distribution' => $responses->groupBy('rating')->map->count()->toArray(),
        ];
    }

    /**
     * Get SLA breach metrics.
     */
    protected function getSlaMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $conversations = Conversation::where('assignee_id', $userId)
            ->whereHas('inbox', fn ($q) => $q->where('account_id', $this->accountId))
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $firstResponseBreaches = $conversations->where('first_response_sla_breached', true)->count();
        $resolutionBreaches = $conversations->where('resolution_sla_breached', true)->count();

        return [
            'breaches' => $firstResponseBreaches + $resolutionBreaches,
            'first_response_breaches' => $firstResponseBreaches,
            'resolution_breaches' => $resolutionBreaches,
            'total_with_sla' => $conversations->whereNotNull('sla_policy_id')->count(),
        ];
    }

    /**
     * Get agent info.
     */
    protected function getAgentInfo(int $userId): array
    {
        $user = User::find($userId);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url ?? null,
        ];
    }

    /**
     * Get leaderboard for a specific metric.
     */
    public function getLeaderboard(string $metric, Carbon $start, Carbon $end, int $limit = 10): Collection
    {
        $agents = User::where('account_id', $this->accountId)
            ->where('role', '!=', 'administrator')
            ->get();

        $leaderboard = $agents->map(function ($agent) use ($metric, $start, $end) {
            $metrics = $this->getAgentMetrics($agent->id, $start, $end);

            $value = match ($metric) {
                'conversations_resolved' => $metrics['conversation']['resolved'] ?? 0,
                'messages_sent' => $metrics['messages']['sent'] ?? 0,
                'first_response_time' => $metrics['response_times']['first_response_avg'] ?? 0,
                'resolution_time' => $metrics['response_times']['resolution_avg'] ?? 0,
                'csat_rating' => $metrics['csat']['avg_rating'] ?? 0,
                default => 0,
            };

            return [
                'agent' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'avatar_url' => $agent->avatar_url ?? null,
                ],
                'value' => $value,
            ];
        });

        // Sort based on metric (lower is better for response times)
        $sortOrder = in_array($metric, ['first_response_time', 'resolution_time']) ? 'asc' : 'desc';

        return $leaderboard
            ->sortBy('value', SORT_REGULAR, $sortOrder === 'desc')
            ->values()
            ->take($limit);
    }

    /**
     * Get team comparison metrics.
     */
    public function getTeamComparison(int $teamId, Carbon $start, Carbon $end): array
    {
        $teamMembers = User::where('account_id', $this->accountId)
            ->where('team_id', $teamId)
            ->get();

        $teamMetrics = $teamMembers->map(function ($agent) use ($start, $end) {
            return array_merge(
                ['agent' => ['id' => $agent->id, 'name' => $agent->name]],
                $this->getAgentMetrics($agent->id, $start, $end)
            );
        });

        return [
            'team_id' => $teamId,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'members' => $teamMetrics->toArray(),
            'aggregated' => [
                'total_conversations' => $teamMetrics->sum('conversation.assigned'),
                'total_resolved' => $teamMetrics->sum('conversation.resolved'),
                'avg_first_response' => round($teamMetrics->avg('response_times.first_response_avg'), 2),
                'avg_resolution_time' => round($teamMetrics->avg('response_times.resolution_avg'), 2),
            ],
        ];
    }

    /**
     * Get trend data for a specific agent and metric.
     */
    public function getTrendData(int $userId, string $metric, int $days = 30): array
    {
        $end = now();
        $start = now()->subDays($days - 1);

        $period = CarbonPeriod::create($start, $end);
        $trendData = [];

        foreach ($period as $date) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayMetrics = $this->getAgentMetrics($userId, $dayStart, $dayEnd);

            $value = match ($metric) {
                'conversations_resolved' => $dayMetrics['conversation']['resolved'] ?? 0,
                'messages_sent' => $dayMetrics['messages']['sent'] ?? 0,
                'first_response_time' => $dayMetrics['response_times']['first_response_avg'] ?? 0,
                'csat_rating' => $dayMetrics['csat']['avg_rating'] ?? 0,
                default => 0,
            };

            $trendData[] = [
                'date' => $date->toDateString(),
                'value' => $value,
            ];
        }

        return $trendData;
    }

    /**
     * Generate and store daily snapshot for an agent.
     */
    public function generateDailySnapshot(int $userId, Carbon $date): AgentPerformanceSnapshot
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $metrics = $this->calculateRealTimeMetrics($userId, $start, $end);

        $user = User::find($userId);

        return AgentPerformanceSnapshot::updateOrCreate(
            [
                'account_id' => $this->accountId,
                'user_id' => $userId,
                'snapshot_date' => $date->toDateString(),
                'snapshot_type' => 'daily',
                'snapshot_hour' => null,
            ],
            [
                'team_id' => $user->team_id,
                'metrics' => [
                    'conversations_assigned' => $metrics['conversation']['assigned'],
                    'conversations_resolved' => $metrics['conversation']['resolved'],
                    'conversations_pending' => $metrics['conversation']['pending'],
                    'messages_sent' => $metrics['messages']['sent'],
                    'first_response_time_avg' => $metrics['response_times']['first_response_avg'],
                    'resolution_time_avg' => $metrics['response_times']['resolution_avg'],
                    'csat_avg_rating' => $metrics['csat']['avg_rating'],
                    'csat_responses' => $metrics['csat']['total_responses'],
                    'sla_breaches' => $metrics['sla']['breaches'],
                    'first_response_breaches' => $metrics['sla']['first_response_breaches'],
                    'resolution_breaches' => $metrics['sla']['resolution_breaches'],
                ],
            ]
        );
    }

    /**
     * Generate snapshots for all agents for a specific date.
     */
    public function generateSnapshotsForAllAgents(Carbon $date): int
    {
        $agents = User::where('account_id', $this->accountId)
            ->where('role', '!=', 'administrator')
            ->get();

        $count = 0;

        foreach ($agents as $agent) {
            $this->generateDailySnapshot($agent->id, $date);
            $count++;
        }

        return $count;
    }
}
