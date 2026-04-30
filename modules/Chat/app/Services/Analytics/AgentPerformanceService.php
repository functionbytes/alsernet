<?php

namespace Modules\Chat\Services\Analytics;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Chat\Models\AgentPerformanceSnapshot;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Csat\CsatSurveyResponse;

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
        if ($end->isYesterday() || $end->isPast()) {
            return $this->getMetricsFromSnapshots($userId, $start, $end);
        }

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
            return $this->calculateRealTimeMetrics($userId, $start, $end);
        }

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
     * Get conversation-related metrics collapsed into a single aggregation query.
     *
     * Replaces 4 separate count queries with one selectRaw aggregation.
     */
    protected function getConversationMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $statusIds = $this->getStatusIds();

        $agg = Conversation::where('assignee_id', $userId)
            ->where('account_id', $this->accountId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as resolved', [$statusIds['resolved'] ?? null])
            ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as pending', [$statusIds['pending'] ?? null])
            ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as open', [$statusIds['open'] ?? null])
            ->first();

        $total = (int) ($agg->total ?? 0);
        $resolved = (int) ($agg->resolved ?? 0);

        return [
            'assigned' => $total,
            'resolved' => $resolved,
            'pending' => (int) ($agg->pending ?? 0),
            'open' => (int) ($agg->open ?? 0),
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get message-related metrics.
     *
     * Uses a join instead of whereHas to avoid correlated subqueries.
     */
    protected function getMessageMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $sent = ConversationMessage::query()
            ->join('chat_conversations as hc', 'chat_conversation_messages.conversation_id', '=', 'hc.id')
            ->where('chat_conversation_messages.sender_type', User::class)
            ->where('chat_conversation_messages.sender_id', $userId)
            ->where('hc.account_id', $this->accountId)
            ->whereBetween('chat_conversation_messages.created_at', [$start, $end])
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
     *
     * Collapses first-response and resolution into one aggregation query.
     */
    protected function getResponseTimeMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $resolvedId = $this->getStatusId('resolved');

        $agg = Conversation::where('assignee_id', $userId)
            ->where('account_id', $this->accountId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as first_response_avg')
            ->selectRaw('AVG(CASE WHEN status_id = ? AND resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) END) as resolution_avg', [$resolvedId])
            ->first();

        return [
            'first_response_avg' => round((float) ($agg->first_response_avg ?? 0), 2),
            'resolution_avg' => round((float) ($agg->resolution_avg ?? 0), 2),
        ];
    }

    /**
     * Get CSAT (Customer Satisfaction) metrics.
     *
     * Uses a join instead of whereHas. Uses the model's table name to avoid hardcoding.
     */
    protected function getCsatMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $csatTable = (new CsatSurveyResponse)->getTable();

        $responses = CsatSurveyResponse::query()
            ->join('chat_conversations as hc', "{$csatTable}.conversation_id", '=', 'hc.id')
            ->where('hc.assignee_id', $userId)
            ->where('hc.account_id', $this->accountId)
            ->whereBetween("{$csatTable}.created_at", [$start, $end])
            ->select("{$csatTable}.*")
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
     *
     * Collapses load + PHP filter into a single selectRaw aggregation query.
     */
    protected function getSlaMetrics(int $userId, Carbon $start, Carbon $end): array
    {
        $agg = Conversation::where('assignee_id', $userId)
            ->where('account_id', $this->accountId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(CASE WHEN first_response_sla_breached = 1 THEN 1 ELSE 0 END) as first_response_breaches')
            ->selectRaw('SUM(CASE WHEN resolution_sla_breached = 1 THEN 1 ELSE 0 END) as resolution_breaches')
            ->selectRaw('SUM(CASE WHEN sla_id IS NOT NULL THEN 1 ELSE 0 END) as total_with_sla')
            ->first();

        $firstResponseBreaches = (int) ($agg->first_response_breaches ?? 0);
        $resolutionBreaches = (int) ($agg->resolution_breaches ?? 0);

        return [
            'breaches' => $firstResponseBreaches + $resolutionBreaches,
            'first_response_breaches' => $firstResponseBreaches,
            'resolution_breaches' => $resolutionBreaches,
            'total_with_sla' => (int) ($agg->total_with_sla ?? 0),
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

        $sortDesc = ! in_array($metric, ['first_response_time', 'resolution_time']);

        return $leaderboard
            ->sortBy('value', SORT_REGULAR, $sortDesc)
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
        $end = now()->endOfDay();
        $start = now()->subDays($days - 1)->startOfDay();

        $snapshots = AgentPerformanceSnapshot::forAgent($userId)
            ->forDateRange($start, $end)
            ->daily()
            ->orderBy('snapshot_date')
            ->get()
            ->keyBy(fn ($s) => $s->snapshot_date->toDateString());

        $period = CarbonPeriod::create($start, $end);

        $missingDates = collect($period)
            ->filter(fn ($date) => ! $snapshots->has($date->toDateString()))
            ->values();

        $batchData = $missingDates->isNotEmpty()
            ? $this->batchTrendQuery($userId, $metric, $start, $end)
            : [];

        $trendData = [];
        foreach ($period as $date) {
            $dateKey = $date->toDateString();

            if ($snapshots->has($dateKey)) {
                $snapshot = $snapshots->get($dateKey);
                $value = match ($metric) {
                    'conversations_resolved' => $snapshot->getMetric('conversations_resolved', 0),
                    'messages_sent' => $snapshot->getMetric('messages_sent', 0),
                    'first_response_time' => $snapshot->getMetric('first_response_time_avg', 0),
                    'csat_rating' => $snapshot->getMetric('csat_avg_rating', 0),
                    default => 0,
                };
            } else {
                $value = $batchData[$dateKey] ?? 0;
            }

            $trendData[] = ['date' => $dateKey, 'value' => $value];
        }

        return $trendData;
    }

    /**
     * Batch query trend data grouped by date.
     *
     * All whereHas calls replaced with direct joins.
     */
    protected function batchTrendQuery(int $userId, string $metric, Carbon $start, Carbon $end): array
    {
        $csatTable = (new CsatSurveyResponse)->getTable();

        $result = match ($metric) {
            'conversations_resolved' => Conversation::where('assignee_id', $userId)
                ->where('account_id', $this->accountId)
                ->where('status_id', $this->getStatusId('resolved'))
                ->whereBetween('resolved_at', [$start, $end])
                ->selectRaw('DATE(resolved_at) as date_key, COUNT(*) as value')
                ->groupBy('date_key')
                ->pluck('value', 'date_key'),
            'messages_sent' => ConversationMessage::query()
                ->join('chat_conversations as hc', 'chat_conversation_messages.conversation_id', '=', 'hc.id')
                ->where('chat_conversation_messages.sender_type', User::class)
                ->where('chat_conversation_messages.sender_id', $userId)
                ->where('hc.account_id', $this->accountId)
                ->whereBetween('chat_conversation_messages.created_at', [$start, $end])
                ->selectRaw('DATE(chat_conversation_messages.created_at) as date_key, COUNT(*) as value')
                ->groupBy('date_key')
                ->pluck('value', 'date_key'),
            'first_response_time' => Conversation::where('assignee_id', $userId)
                ->where('account_id', $this->accountId)
                ->whereNotNull('first_response_at')
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date_key, AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as value')
                ->groupBy('date_key')
                ->pluck('value', 'date_key'),
            'csat_rating' => CsatSurveyResponse::query()
                ->join('chat_conversations as hc', "{$csatTable}.conversation_id", '=', 'hc.id')
                ->where('hc.assignee_id', $userId)
                ->where('hc.account_id', $this->accountId)
                ->whereBetween("{$csatTable}.created_at", [$start, $end])
                ->selectRaw("DATE({$csatTable}.created_at) as date_key, AVG({$csatTable}.rating) as value")
                ->groupBy('date_key')
                ->pluck('value', 'date_key'),
            default => collect(),
        };

        return $result->map(fn ($v) => round((float) $v, 2))->toArray();
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

        foreach ($agents as $agent) {
            $this->generateDailySnapshot($agent->id, $date);
        }

        return $agents->count();
    }

    /**
     * Load all status slugs → IDs for this account in one query, cached for 60 minutes.
     *
     * @return array<string, int>
     */
    protected function getStatusIds(): array
    {
        return Cache::remember("agent_perf:status_ids:{$this->accountId}", now()->addMinutes(60), function () {
            return ConversationStatus::where('account_id', $this->accountId)
                ->pluck('id', 'slug')
                ->toArray();
        });
    }

    /**
     * Get the ID for a single status slug, or null if it doesn't exist.
     */
    protected function getStatusId(string $slug): ?int
    {
        return $this->getStatusIds()[$slug] ?? null;
    }
}
