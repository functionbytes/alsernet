<?php

namespace Modules\Chat\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Config\PerformanceThresholds;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;

class DashboardMetricsService
{
    protected int $accountId;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * Get all dashboard metrics.
     *
     * @return array<string, array>
     */
    public function getAllMetrics(): array
    {
        return [
            'overview' => $this->getOverviewMetrics(),
            'conversation' => $this->getConversationMetrics(),
            'agents' => $this->getAgentMetrics(),
            'response_times' => $this->getResponseTimeMetrics(),
            'trending' => $this->getTrendingMetrics(),
            'comparisons' => $this->getPeriodComparisons(),
            'alerts' => $this->getPerformanceAlerts(),
        ];
    }

    /**
     * Get overview metrics (top-level dashboard cards).
     *
     * Cached for 5 minutes. Collapses all conversation counts into one aggregation query.
     *
     * @return array{total_conversations: int, open_conversations: int, resolved_today: int,
     *               avg_response_time: float, active_agents: int, total_messages_today: int}
     */
    public function getOverviewMetrics(): array
    {
        $cacheKey = "dashboard:overview:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(PerformanceThresholds::CACHE_OVERVIEW_METRICS_TTL), function () {
            $statusIds = $this->getStatusIds();
            $today = today()->toDateString();

            // Single aggregation query for all conversation-based counts
            $agg = Conversation::query()
                ->where('account_id', $this->accountId)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as open_count', [$statusIds['open']])
                ->selectRaw('SUM(CASE WHEN status_id = ? AND DATE(updated_at) = ? THEN 1 ELSE 0 END) as resolved_today', [$statusIds['resolved'], $today])
                ->selectRaw('AVG(CASE WHEN status_id = ? AND first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_response_time', [$statusIds['resolved']])
                ->first();

            return [
                'total_conversations' => (int) ($agg->total ?? 0),
                'open_conversations' => (int) ($agg->open_count ?? 0),
                'resolved_today' => (int) ($agg->resolved_today ?? 0),
                'avg_response_time' => round((float) ($agg->avg_response_time ?? 0), 2),
                'active_agents' => $this->getActiveAgents(),
                'total_messages_today' => $this->getMessagesToday(),
            ];
        });
    }

    /**
     * Get conversation metrics grouped by status, priority, inbox, and hourly trend.
     *
     * @return array{by_status: array, by_priority: array, by_inbox: array, hourly_trend: array}
     */
    public function getConversationMetrics(): array
    {
        $cacheKey = "dashboard:conversations:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return [
                'by_status' => $this->getConversationsByStatus(),
                'by_priority' => $this->getConversationsByPriority(),
                'by_inbox' => $this->getConversationsByInbox(),
                'hourly_trend' => $this->getHourlyConversationTrend(),
            ];
        });
    }

    /**
     * Get agent performance and workload metrics.
     *
     * @return array{top_performers: array, agent_workload: array, availability_distribution: array}
     */
    public function getAgentMetrics(): array
    {
        $cacheKey = "dashboard:agents:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(PerformanceThresholds::CACHE_AGENT_METRICS_TTL), function () {
            return [
                'top_performers' => $this->getTopPerformers(),
                'agent_workload' => $this->getAgentWorkload(),
                'availability_distribution' => $this->getAvailabilityDistribution(),
            ];
        });
    }

    /**
     * Get response time metrics by multiple dimensions.
     *
     * @return array{average: float, first_response: float, by_agent: array, trend: array}
     */
    public function getResponseTimeMetrics(): array
    {
        $cacheKey = "dashboard:response_times:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return [
                'average' => $this->getAverageResponseTime(),
                'first_response' => $this->getAverageFirstResponseTime(),
                'by_agent' => $this->getResponseTimeByAgent(),
                'trend' => $this->getResponseTimeTrend(),
            ];
        });
    }

    /**
     * Get trending metrics over last 7 days.
     *
     * @return array{conversations_7d: array, messages_7d: array, resolution_rate: float}
     */
    public function getTrendingMetrics(): array
    {
        $cacheKey = "dashboard:trending:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(PerformanceThresholds::CACHE_TRENDS_TTL), function () {
            return [
                'conversations_7d' => $this->getLast7DaysTrend('conversation'),
                'messages_7d' => $this->getLast7DaysTrend('messages'),
                'resolution_rate' => $this->getResolutionRate(),
            ];
        });
    }

    /**
     * Get count of active agents (online or busy) for the account.
     *
     * Queries chat_agent_presence directly since users lack account_id
     * and availability_status columns.
     */
    protected function getActiveAgents(): int
    {
        return DB::table('chat_agent_presence')
            ->where('account_id', $this->accountId)
            ->whereIn('status', ['online', 'busy'])
            ->count();
    }

    /**
     * Get count of messages sent today.
     * Uses account_id directly on messages — no join needed.
     */
    protected function getMessagesToday(): int
    {
        return ConversationMessage::where('account_id', $this->accountId)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get average response time in minutes for resolved conversations.
     */
    protected function getAverageResponseTime(): float
    {
        $result = Conversation::where('account_id', $this->accountId)
            ->where('status_id', $this->getStatusId('resolved'))
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_time')
            ->first();

        return round((float) ($result->avg_time ?? 0), 2);
    }

    /**
     * Get average first response time in minutes.
     */
    protected function getAverageFirstResponseTime(): float
    {
        return $this->getAverageResponseTime();
    }

    /**
     * Get conversation count grouped by status (single join query).
     *
     * @return array<string, int>
     */
    protected function getConversationsByStatus(): array
    {
        return Conversation::query()
            ->where('chat_conversations.account_id', $this->accountId)
            ->join('chat_conversation_statuses as cs', 'chat_conversations.status_id', '=', 'cs.id')
            ->selectRaw('cs.slug as status, COUNT(*) as count')
            ->groupBy('cs.slug')
            ->get()
            ->pluck('count', 'status')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /**
     * Get conversation count grouped by priority level.
     *
     * @return array<string, int>
     */
    protected function getConversationsByPriority(): array
    {
        return Conversation::query()
            ->where('chat_conversations.account_id', $this->accountId)
            ->leftJoin('chat_conversation_priorities as cp', 'chat_conversations.priority_id', '=', 'cp.id')
            ->selectRaw('COALESCE(cp.slug, \'none\') as priority, COUNT(*) as count')
            ->groupBy('cp.slug')
            ->get()
            ->pluck('count', 'priority')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /**
     * Get top 5 inboxes by conversation count.
     *
     * @return array<string, int>
     */
    protected function getConversationsByInbox(): array
    {
        return Conversation::select('chat_inboxes.name', DB::raw('COUNT(*) as count'))
            ->join('chat_inboxes', 'chat_conversations.inbox_id', '=', 'chat_inboxes.id')
            ->where('chat_inboxes.account_id', $this->accountId)
            ->groupBy('chat_inboxes.id', 'chat_inboxes.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Get hourly conversation trend for the last 24 hours.
     *
     * @return array<int, int>
     */
    protected function getHourlyConversationTrend(): array
    {
        $data = Conversation::where('account_id', $this->accountId)
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $trend = array_fill(0, 24, 0);
        foreach ($data as $item) {
            $trend[$item->hour] = $item->count;
        }

        return $trend;
    }

    /**
     * Get top performing agents by resolved conversations in the last 7 days.
     *
     * Scopes agents to the account via chat_accounts_user pivot.
     * Users have firstname/lastname (not name) and no account_id column.
     *
     * @return array<int, array{name: string, resolved_count: int}>
     */
    protected function getTopPerformers(int $limit = 5): array
    {
        $resolvedId = $this->getStatusId('resolved');

        return DB::table('users')
            ->join('chat_accounts_user as pau', 'pau.user_id', '=', 'users.id')
            ->join('chat_conversations as hc', function ($join) use ($resolvedId) {
                $join->on('hc.assignee_id', '=', 'users.id')
                    ->where('hc.account_id', $this->accountId)
                    ->where('hc.status_id', $resolvedId)
                    ->where('hc.updated_at', '>=', now()->subDays(7))
                    ->whereNull('hc.deleted_at');
            })
            ->where('pau.account_id', $this->accountId)
            ->selectRaw('CONCAT(users.firstname, \' \', users.lastname) as name, COUNT(hc.id) as resolved_count')
            ->groupBy('users.id', 'users.firstname', 'users.lastname')
            ->orderByDesc('resolved_count')
            ->limit($limit)
            ->get()
            ->map(fn ($user) => [
                'name' => $user->name,
                'resolved_count' => (int) $user->resolved_count,
            ])
            ->toArray();
    }

    /**
     * Get current workload distribution across all agents in the account.
     *
     * Scopes via chat_accounts_user pivot. Availability status comes from
     * chat_agent_presence, not the users table.
     *
     * @return array<int, array{name: string, open_conversations: int, status: string}>
     */
    protected function getAgentWorkload(): array
    {
        $openId = $this->getStatusId('open');

        return DB::table('users')
            ->join('chat_accounts_user as pau', function ($join) {
                $join->on('pau.user_id', '=', 'users.id')
                    ->where('pau.account_id', $this->accountId);
            })
            ->leftJoin('chat_agent_presence as ap', function ($join) {
                $join->on('ap.user_id', '=', 'users.id')
                    ->where('ap.account_id', $this->accountId);
            })
            ->leftJoin('chat_conversations as hc', function ($join) use ($openId) {
                $join->on('hc.assignee_id', '=', 'users.id')
                    ->where('hc.account_id', $this->accountId)
                    ->where('hc.status_id', $openId)
                    ->whereNull('hc.deleted_at');
            })
            ->selectRaw('CONCAT(users.firstname, \' \', users.lastname) as name, COALESCE(ap.status, \'offline\') as availability_status, COUNT(hc.id) as open_count')
            ->groupBy('users.id', 'users.firstname', 'users.lastname', 'ap.status')
            ->orderByDesc('open_count')
            ->get()
            ->map(fn ($user) => [
                'name' => $user->name,
                'open_conversations' => (int) $user->open_count,
                'status' => $user->availability_status,
            ])
            ->toArray();
    }

    /**
     * Get distribution of agent availability statuses for the account.
     *
     * Reads from chat_agent_presence since users lack availability_status.
     * Agents with no presence record are counted as 'offline'.
     *
     * @return array<string, int>
     */
    protected function getAvailabilityDistribution(): array
    {
        $presenceCounts = DB::table('chat_agent_presence')
            ->where('account_id', $this->accountId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAgents = DB::table('chat_accounts_user')
            ->where('account_id', $this->accountId)
            ->count();

        $activeCount = array_sum($presenceCounts);
        $offlineCount = max(0, $totalAgents - $activeCount);

        return array_merge(['offline' => $offlineCount], $presenceCounts);
    }

    /**
     * Get average response time grouped by agent (top 10, fastest first).
     *
     * Scopes agents via chat_accounts_user pivot. Users have firstname/lastname,
     * not a single name column.
     *
     * @return array<int, array{name: string, avg_response_time: float}>
     */
    protected function getResponseTimeByAgent(): array
    {
        return DB::table('users')
            ->join('chat_accounts_user as pau', function ($join) {
                $join->on('pau.user_id', '=', 'users.id')
                    ->where('pau.account_id', $this->accountId);
            })
            ->join('chat_conversations as hc', function ($join) {
                $join->on('hc.assignee_id', '=', 'users.id')
                    ->where('hc.account_id', $this->accountId)
                    ->whereNotNull('hc.first_response_at')
                    ->whereNull('hc.deleted_at');
            })
            ->selectRaw('CONCAT(users.firstname, \' \', users.lastname) as name, AVG(TIMESTAMPDIFF(MINUTE, hc.created_at, hc.first_response_at)) as avg_time')
            ->groupBy('users.id', 'users.firstname', 'users.lastname')
            ->orderBy('avg_time')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'avg_response_time' => round((float) $item->avg_time, 2),
            ])
            ->toArray();
    }

    /**
     * Get response time trend over the last 7 days.
     *
     * @return array<int, array{date: string, avg_time: float}>
     */
    protected function getResponseTimeTrend(): array
    {
        return Conversation::where('account_id', $this->accountId)
            ->whereNotNull('first_response_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->date,
                'avg_time' => round((float) $item->avg_time, 2),
            ])
            ->toArray();
    }

    /**
     * Get daily count trend for last 7 days.
     *
     * @return array<int, array{date: string, count: int}>
     */
    protected function getLast7DaysTrend(string $entity): array
    {
        $query = $entity === 'conversation'
            ? Conversation::where('account_id', $this->accountId)
            : ConversationMessage::where('account_id', $this->accountId);

        return $query
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
            ])
            ->toArray();
    }

    /**
     * Get resolution rate as a percentage.
     *
     * Uses a single aggregation query instead of two separate counts.
     */
    protected function getResolutionRate(): float
    {
        $result = Conversation::where('account_id', $this->accountId)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as resolved', [$this->getStatusId('resolved')])
            ->first();

        $total = (int) ($result->total ?? 0);

        if ($total === 0) {
            return 0.0;
        }

        return round(((int) $result->resolved / $total) * 100, 2);
    }

    /**
     * Get month-over-month period comparisons.
     *
     * Uses one aggregation query per period instead of four separate queries.
     * Cached for 15 minutes.
     *
     * @return array<string, array{current: int|float, previous: int|float, change_percent: float, trend: string}>
     */
    public function getPeriodComparisons(): array
    {
        $cacheKey = "dashboard:comparisons:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(PerformanceThresholds::CACHE_TRENDS_TTL), function () {
            $resolvedId = $this->getStatusId('resolved');

            $currentStart = now()->startOfMonth();
            $currentEnd = now();
            $previousStart = now()->subMonth()->startOfMonth();
            $previousEnd = now()->subMonth()->endOfMonth();

            [$current, $previous] = $this->getPeriodMetricsPair(
                $resolvedId, $currentStart, $currentEnd, $previousStart, $previousEnd
            );

            return [
                'conversation' => $this->buildComparison($previous['conversations'], $current['conversations']),
                'resolved' => $this->buildComparison($previous['resolved'], $current['resolved']),
                'messages' => $this->buildComparison($previous['messages'], $current['messages']),
                'response_time' => $this->buildComparison($previous['avg_response'], $current['avg_response'], lowerIsBetter: true),
            ];
        });
    }

    /**
     * Fetch current and previous period metrics in 3 queries total.
     *
     * @return array{0: array, 1: array}
     */
    protected function getPeriodMetricsPair(
        ?int $resolvedId,
        $currentStart,
        $currentEnd,
        $previousStart,
        $previousEnd
    ): array {
        $buildPeriodAgg = function ($start, $end) use ($resolvedId): array {
            $conv = Conversation::where('account_id', $this->accountId)
                ->selectRaw('COUNT(*) as conversations')
                ->selectRaw('SUM(CASE WHEN status_id = ? AND updated_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as resolved', [$resolvedId, $start, $end])
                ->selectRaw('AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_response')
                ->whereBetween('created_at', [$start, $end])
                ->first();

            $messages = ConversationMessage::where('account_id', $this->accountId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            return [
                'conversations' => (int) ($conv->conversations ?? 0),
                'resolved' => (int) ($conv->resolved ?? 0),
                'avg_response' => round((float) ($conv->avg_response ?? 0), 2),
                'messages' => $messages,
            ];
        };

        return [$buildPeriodAgg($previousStart, $previousEnd), $buildPeriodAgg($currentStart, $currentEnd)];
    }

    /**
     * Build a comparison array for a single metric.
     *
     * @return array{current: int|float, previous: int|float, change_percent: float, trend: string}
     */
    protected function buildComparison(int|float $previous, int|float $current, bool $lowerIsBetter = false): array
    {
        return [
            'current' => $current,
            'previous' => $previous,
            'change_percent' => $this->calculatePercentageChange($previous, $current),
            'trend' => $this->getTrend($previous, $current, $lowerIsBetter),
        ];
    }

    /**
     * Get performance alerts based on configured thresholds.
     *
     * Uses a single join query for the overloaded agents check.
     * Cached for 3 minutes.
     *
     * @return array<int, array{type: string, title: string, message: string, priority: string}>
     */
    public function getPerformanceAlerts(): array
    {
        $cacheKey = "dashboard:alerts:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(PerformanceThresholds::CACHE_ALERTS_TTL), function () {
            $alerts = [];
            $openId = $this->getStatusId('open');
            $resolvedId = $this->getStatusId('resolved');

            // One aggregation query for avg response time + unassigned open count
            $agg = Conversation::where('account_id', $this->accountId)
                ->selectRaw('AVG(CASE WHEN status_id = ? AND first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_response', [$resolvedId])
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as resolved_count', [$resolvedId])
                ->selectRaw('SUM(CASE WHEN status_id = ? AND assignee_id IS NULL THEN 1 ELSE 0 END) as unassigned_open', [$openId])
                ->first();

            $avgResponseTime = round((float) ($agg->avg_response ?? 0), 2);
            $total = (int) ($agg->total ?? 0);
            $resolvedCount = (int) ($agg->resolved_count ?? 0);
            $unassigned = (int) ($agg->unassigned_open ?? 0);

            if ($avgResponseTime > PerformanceThresholds::MAX_RESPONSE_TIME_MINUTES) {
                $max = PerformanceThresholds::MAX_RESPONSE_TIME_MINUTES;
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'High Response Time',
                    'message' => "Average response time is {$avgResponseTime} minutes. Target is under {$max} minutes.",
                    'priority' => 'high',
                ];
            }

            $resolutionRate = $total > 0 ? round(($resolvedCount / $total) * 100, 2) : 0.0;

            if ($resolutionRate < PerformanceThresholds::MIN_RESOLUTION_RATE_PERCENT) {
                $min = PerformanceThresholds::MIN_RESOLUTION_RATE_PERCENT;
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Low Resolution Rate',
                    'message' => "Resolution rate is {$resolutionRate}%. Target is above {$min}%.",
                    'priority' => 'medium',
                ];
            }

            // Overloaded agents: join via pivot to scope by account_id
            $overloadedAgents = DB::table('users')
                ->join('chat_accounts_user as pau', function ($join) {
                    $join->on('pau.user_id', '=', 'users.id')
                        ->where('pau.account_id', $this->accountId);
                })
                ->join('chat_conversations as hc', function ($join) use ($openId) {
                    $join->on('hc.assignee_id', '=', 'users.id')
                        ->where('hc.account_id', $this->accountId)
                        ->where('hc.status_id', $openId)
                        ->whereNull('hc.deleted_at');
                })
                ->groupBy('users.id')
                ->havingRaw('COUNT(hc.id) > ?', [PerformanceThresholds::MAX_CONVERSATIONS_PER_AGENT])
                ->selectRaw('users.id')
                ->count();

            if ($overloadedAgents > 0) {
                $max = PerformanceThresholds::MAX_CONVERSATIONS_PER_AGENT;
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Agent Overload',
                    'message' => "{$overloadedAgents} agent(s) have more than {$max} open conversation.",
                    'priority' => 'medium',
                ];
            }

            if ($unassigned > PerformanceThresholds::MAX_UNASSIGNED_CONVERSATIONS) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Unassigned Conversations',
                    'message' => "{$unassigned} conversation are waiting for assignment.",
                    'priority' => 'high',
                ];
            }

            return $alerts;
        });
    }

    /**
     * Invalidate all dashboard cache keys for this account.
     * Should be called from ConversationObserver on create/update/delete.
     */
    public function invalidateCache(): void
    {
        $keys = [
            "dashboard:overview:{$this->accountId}",
            "dashboard:conversations:{$this->accountId}",
            "dashboard:agents:{$this->accountId}",
            "dashboard:response_times:{$this->accountId}",
            "dashboard:trending:{$this->accountId}",
            "dashboard:comparisons:{$this->accountId}",
            "dashboard:alerts:{$this->accountId}",
            "dashboard:status_ids:{$this->accountId}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculatePercentageChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }

        return round((($new - $old) / $old) * 100, 2);
    }

    /**
     * Determine trend direction based on value comparison.
     */
    protected function getTrend(float $old, float $new, bool $lowerIsBetter = false): string
    {
        if ($new > $old) {
            return $lowerIsBetter ? 'down' : 'up';
        }

        if ($new < $old) {
            return $lowerIsBetter ? 'up' : 'down';
        }

        return 'neutral';
    }

    /**
     * Load all status slugs → IDs for this account in one query, cached for 60 minutes.
     * Status records are rarely changed, so a long TTL is appropriate.
     *
     * @return array<string, int|null>
     */
    protected function getStatusIds(): array
    {
        $cacheKey = "dashboard:status_ids:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () {
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
