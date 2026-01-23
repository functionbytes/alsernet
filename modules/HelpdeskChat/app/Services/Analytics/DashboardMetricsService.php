<?php

namespace Modules\HelpdeskChat\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class DashboardMetricsService
{
    protected int $accountId;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * Get all dashboard metrics.
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
     * Get overview metrics (top cards).
     */
    public function getOverviewMetrics(): array
    {
        $cacheKey = "dashboard:overview:{$this->accountId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return [
                'total_conversations' => $this->getTotalConversations(),
                'open_conversations' => $this->getOpenConversations(),
                'resolved_today' => $this->getResolvedToday(),
                'avg_response_time' => $this->getAverageResponseTime(),
                'active_agents' => $this->getActiveAgents(),
                'total_messages_today' => $this->getMessagesToday(),
            ];
        });
    }

    /**
     * Get conversation metrics.
     */
    public function getConversationMetrics(): array
    {
        return [
            'by_status' => $this->getConversationsByStatus(),
            'by_priority' => $this->getConversationsByPriority(),
            'by_inbox' => $this->getConversationsByInbox(),
            'hourly_trend' => $this->getHourlyConversationTrend(),
        ];
    }

    /**
     * Get agent performance metrics.
     */
    public function getAgentMetrics(): array
    {
        return [
            'top_performers' => $this->getTopPerformers(),
            'agent_workload' => $this->getAgentWorkload(),
            'availability_distribution' => $this->getAvailabilityDistribution(),
        ];
    }

    /**
     * Get response time metrics.
     */
    public function getResponseTimeMetrics(): array
    {
        return [
            'average' => $this->getAverageResponseTime(),
            'first_response' => $this->getAverageFirstResponseTime(),
            'by_agent' => $this->getResponseTimeByAgent(),
            'trend' => $this->getResponseTimeTrend(),
        ];
    }

    /**
     * Get trending metrics.
     */
    public function getTrendingMetrics(): array
    {
        return [
            'conversations_7d' => $this->getLast7DaysTrend('conversation'),
            'messages_7d' => $this->getLast7DaysTrend('messages'),
            'resolution_rate' => $this->getResolutionRate(),
        ];
    }

    /**
     * Total conversation.
     */
    protected function getTotalConversations(): int
    {
        return Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })->count();
    }

    /**
     * Open conversation count.
     */
    protected function getOpenConversations(): int
    {
        return Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })->where('status', 'open')->count();
    }

    /**
     * Conversations resolved today.
     */
    protected function getResolvedToday(): int
    {
        return Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->where('status', 'resolved')
            ->whereDate('updated_at', today())
            ->count();
    }

    /**
     * Average response time in minutes.
     */
    protected function getAverageResponseTime(): float
    {
        $conversations = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->where('status', 'resolved')
            ->whereNotNull('first_response_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_time'))
            ->first();

        return round($conversations->avg_time ?? 0, 2);
    }

    /**
     * Average first response time.
     */
    protected function getAverageFirstResponseTime(): float
    {
        return $this->getAverageResponseTime();
    }

    /**
     * Active agents count.
     */
    protected function getActiveAgents(): int
    {
        return User::where('account_id', $this->accountId)
            ->whereIn('availability_status', ['online', 'busy'])
            ->count();
    }

    /**
     * Messages sent today.
     */
    protected function getMessagesToday(): int
    {
        return Message::whereHas('conversation.inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Conversations by status.
     */
    protected function getConversationsByStatus(): array
    {
        $data = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return $data->mapWithKeys(fn ($item) => [$item->status => $item->count])->toArray();
    }

    /**
     * Conversations by priority.
     */
    protected function getConversationsByPriority(): array
    {
        $data = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();

        return $data->mapWithKeys(fn ($item) => [
            $item->priority ?? 'none' => $item->count,
        ])->toArray();
    }

    /**
     * Conversations by inbox.
     */
    protected function getConversationsByInbox(): array
    {
        return Conversation::select('inboxes.name', DB::raw('COUNT(*) as count'))
            ->join('inboxes', 'conversation.inbox_id', '=', 'inboxes.id')
            ->where('inboxes.account_id', $this->accountId)
            ->groupBy('inboxes.id', 'inboxes.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Hourly conversation trend (last 24 hours).
     */
    protected function getHourlyConversationTrend(): array
    {
        $data = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->where('created_at', '>=', now()->subDay())
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
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
     * Top performing agents.
     */
    protected function getTopPerformers(int $limit = 5): array
    {
        return User::where('account_id', $this->accountId)
            ->withCount(['assignedConversations' => function ($query) {
                $query->where('status', 'resolved')
                    ->whereDate('updated_at', '>=', now()->subDays(7));
            }])
            ->orderByDesc('assigned_conversations_count')
            ->limit($limit)
            ->get()
            ->map(fn ($user) => [
                'name' => $user->name,
                'resolved_count' => $user->assigned_conversations_count,
            ])
            ->toArray();
    }

    /**
     * Agent workload distribution.
     */
    protected function getAgentWorkload(): array
    {
        return User::where('account_id', $this->accountId)
            ->withCount(['assignedConversations' => function ($query) {
                $query->where('status', 'open');
            }])
            ->orderByDesc('assigned_conversations_count')
            ->get()
            ->map(fn ($user) => [
                'name' => $user->name,
                'open_conversations' => $user->assigned_conversations_count,
                'status' => $user->availability_status,
            ])
            ->toArray();
    }

    /**
     * Availability distribution.
     */
    protected function getAvailabilityDistribution(): array
    {
        return User::where('account_id', $this->accountId)
            ->select('availability_status', DB::raw('COUNT(*) as count'))
            ->groupBy('availability_status')
            ->get()
            ->pluck('count', 'availability_status')
            ->toArray();
    }

    /**
     * Response time by agent.
     */
    protected function getResponseTimeByAgent(): array
    {
        return User::where('users.account_id', $this->accountId)
            ->select('users.name')
            ->join('conversation', 'users.id', '=', 'conversation.assignee_id')
            ->where('conversation.account_id', $this->accountId)
            ->whereNotNull('conversation.first_response_at')
            ->groupBy('users.id', 'users.name')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, conversation.created_at, conversation.first_response_at)) as avg_time')
            ->orderBy('avg_time')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'avg_response_time' => round($item->avg_time, 2),
            ])
            ->toArray();
    }

    /**
     * Response time trend (last 7 days).
     */
    protected function getResponseTimeTrend(): array
    {
        $data = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->whereNotNull('first_response_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->map(fn ($item) => [
            'date' => $item->date,
            'avg_time' => round($item->avg_time, 2),
        ])->toArray();
    }

    /**
     * Last 7 days trend for entity.
     */
    protected function getLast7DaysTrend(string $entity): array
    {
        $model = $entity === 'conversation' ? Conversation::class : Message::class;

        $query = $model::query();

        if ($entity === 'conversation') {
            $query->whereHas('inbox', function ($q) {
                $q->where('account_id', $this->accountId);
            });
        } else {
            $query->whereHas('conversation.inbox', function ($q) {
                $q->where('account_id', $this->accountId);
            });
        }

        $data = $query->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->map(fn ($item) => [
            'date' => $item->date,
            'count' => $item->count,
        ])->toArray();
    }

    /**
     * Resolution rate percentage.
     */
    protected function getResolutionRate(): float
    {
        $total = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })->count();

        if ($total === 0) {
            return 0;
        }

        $resolved = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })->where('status', 'resolved')->count();

        return round(($resolved / $total) * 100, 2);
    }

    /**
     * Get period-over-period comparisons.
     */
    public function getPeriodComparisons(): array
    {
        $currentPeriod = [
            'start' => now()->startOfMonth(),
            'end' => now(),
        ];

        $previousPeriod = [
            'start' => now()->subMonth()->startOfMonth(),
            'end' => now()->subMonth()->endOfMonth(),
        ];

        // Current period metrics
        $currentConversations = $this->getConversationsInPeriod($currentPeriod['start'], $currentPeriod['end']);
        $currentResolved = $this->getResolvedInPeriod($currentPeriod['start'], $currentPeriod['end']);
        $currentMessages = $this->getMessagesInPeriod($currentPeriod['start'], $currentPeriod['end']);
        $currentResponseTime = $this->getAvgResponseTimeInPeriod($currentPeriod['start'], $currentPeriod['end']);

        // Previous period metrics
        $previousConversations = $this->getConversationsInPeriod($previousPeriod['start'], $previousPeriod['end']);
        $previousResolved = $this->getResolvedInPeriod($previousPeriod['start'], $previousPeriod['end']);
        $previousMessages = $this->getMessagesInPeriod($previousPeriod['start'], $previousPeriod['end']);
        $previousResponseTime = $this->getAvgResponseTimeInPeriod($previousPeriod['start'], $previousPeriod['end']);

        return [
            'conversation' => [
                'current' => $currentConversations,
                'previous' => $previousConversations,
                'change_percent' => $this->calculatePercentageChange($previousConversations, $currentConversations),
                'trend' => $this->getTrend($previousConversations, $currentConversations),
            ],
            'resolved' => [
                'current' => $currentResolved,
                'previous' => $previousResolved,
                'change_percent' => $this->calculatePercentageChange($previousResolved, $currentResolved),
                'trend' => $this->getTrend($previousResolved, $currentResolved),
            ],
            'messages' => [
                'current' => $currentMessages,
                'previous' => $previousMessages,
                'change_percent' => $this->calculatePercentageChange($previousMessages, $currentMessages),
                'trend' => $this->getTrend($previousMessages, $currentMessages),
            ],
            'response_time' => [
                'current' => $currentResponseTime,
                'previous' => $previousResponseTime,
                'change_percent' => $this->calculatePercentageChange($previousResponseTime, $currentResponseTime, true),
                'trend' => $this->getTrend($previousResponseTime, $currentResponseTime, true),
            ],
        ];
    }

    /**
     * Get performance alerts.
     */
    public function getPerformanceAlerts(): array
    {
        $alerts = [];

        // High response time alert
        $avgResponseTime = $this->getAverageResponseTime();
        if ($avgResponseTime > 60) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Response Time',
                'message' => "Average response time is {$avgResponseTime} minutes. Target is under 60 minutes.",
                'priority' => 'high',
            ];
        }

        // Low resolution rate alert
        $resolutionRate = $this->getResolutionRate();
        if ($resolutionRate < 70) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Resolution Rate',
                'message' => "Resolution rate is {$resolutionRate}%. Target is above 70%.",
                'priority' => 'medium',
            ];
        }

        // Overloaded agents alert
        $overloadedAgents = User::where('account_id', $this->accountId)
            ->withCount(['assignedConversations' => function ($query) {
                $query->where('status', 'open');
            }])
            ->having('assigned_conversations_count', '>', 15)
            ->count();

        if ($overloadedAgents > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Agent Overload',
                'message' => "{$overloadedAgents} agent(s) have more than 15 open conversation.",
                'priority' => 'medium',
            ];
        }

        // Unassigned conversation alert
        $unassigned = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->whereNull('assignee_id')
            ->where('status', 'open')
            ->count();

        if ($unassigned > 5) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Unassigned Conversations',
                'message' => "{$unassigned} conversation are waiting for assignment.",
                'priority' => 'high',
            ];
        }

        return $alerts;
    }

    /**
     * Helper: Get conversation in period.
     */
    protected function getConversationsInPeriod($start, $end): int
    {
        return Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Helper: Get resolved conversation in period.
     */
    protected function getResolvedInPeriod($start, $end): int
    {
        return Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->where('status', 'resolved')
            ->whereBetween('updated_at', [$start, $end])
            ->count();
    }

    /**
     * Helper: Get messages in period.
     */
    protected function getMessagesInPeriod($start, $end): int
    {
        return Message::whereHas('conversation.inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Helper: Get avg response time in period.
     */
    protected function getAvgResponseTimeInPeriod($start, $end): float
    {
        $result = Conversation::whereHas('inbox', function ($query) {
            $query->where('account_id', $this->accountId);
        })
            ->whereNotNull('first_response_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_time')
            ->first();

        return round($result->avg_time ?? 0, 2);
    }

    /**
     * Calculate percentage change.
     */
    protected function calculatePercentageChange(float $old, float $new, bool $lowerIsBetter = false): float
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }

        return round((($new - $old) / $old) * 100, 2);
    }

    /**
     * Get trend direction.
     */
    protected function getTrend(float $old, float $new, bool $lowerIsBetter = false): string
    {
        if ($new > $old) {
            return $lowerIsBetter ? 'down' : 'up';
        } elseif ($new < $old) {
            return $lowerIsBetter ? 'up' : 'down';
        }

        return 'neutral';
    }
}
