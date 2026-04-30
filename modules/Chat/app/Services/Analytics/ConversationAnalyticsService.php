<?php

namespace Modules\Chat\Services\Analytics;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationStatus;

class ConversationAnalyticsService
{
    /**
     * Get conversation overview metrics for a date range.
     *
     * Collapses total + resolved counts and avg resolution time into one query.
     */
    public function getOverviewMetrics(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $resolvedId = $this->getStatusId($accountId, 'resolved');

        $agg = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as resolved_count', [$resolvedId])
            ->selectRaw('AVG(CASE WHEN status_id = ? AND updated_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) END) as avg_resolution_seconds', [$resolvedId])
            ->first();

        $total = (int) ($agg->total ?? 0);
        $resolved = (int) ($agg->resolved_count ?? 0);
        $avgResolutionSeconds = (float) ($agg->avg_resolution_seconds ?? 0);

        $avgResponseTime = $this->getAverageResponseTime($accountId, $startDate, $endDate);

        return [
            'total_conversations' => $total,
            'resolved_conversations' => $resolved,
            'open_conversations' => $total - $resolved,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            'avg_response_time' => $avgResponseTime,
            'avg_resolution_time' => $avgResolutionSeconds > 0 ? round($avgResolutionSeconds / 3600, 2) : 0.0,
        ];
    }

    /**
     * Get average first response time in minutes.
     *
     * Uses a join-based approach to find first incoming and first outgoing messages.
     */
    protected function getAverageResponseTime(int $accountId, Carbon $startDate, Carbon $endDate): float
    {
        $result = DB::table('chat_conversations as c')
            ->join('chat_conversation_messages as m1', function ($join) {
                $join->on('c.id', '=', 'm1.conversation_id')
                    ->where('m1.message_type', 'incoming')
                    ->whereRaw('m1.id = (
                        SELECT id FROM chat_conversation_messages
                        WHERE conversation_id = c.id
                        AND message_type = \'incoming\'
                        ORDER BY created_at ASC
                        LIMIT 1
                    )');
            })
            ->join('chat_conversation_messages as m2', function ($join) {
                $join->on('c.id', '=', 'm2.conversation_id')
                    ->where('m2.message_type', 'outgoing')
                    ->where('m2.sender_type', User::class)
                    ->whereRaw('m2.id = (
                        SELECT id FROM chat_conversation_messages
                        WHERE conversation_id = c.id
                        AND message_type = \'outgoing\'
                        AND sender_type = ?
                        ORDER BY created_at ASC
                        LIMIT 1
                    )', [User::class]);
            })
            ->where('c.account_id', $accountId)
            ->whereBetween('c.created_at', [$startDate, $endDate])
            ->whereRaw('m2.created_at > m1.created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_seconds')
            ->first();

        return $result && $result->avg_seconds
            ? round($result->avg_seconds / 60, 2)
            : 0.0;
    }

    /**
     * Get conversation grouped by day.
     */
    public function getConversationsByDay(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        return Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn ($item) => [Carbon::parse($item->date)->format('M d') => $item->count])
            ->toArray();
    }

    /**
     * Get conversation grouped by channel.
     */
    public function getConversationsByChannel(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        return Conversation::where('chat_conversations.account_id', $accountId)
            ->whereBetween('chat_conversations.created_at', [$startDate, $endDate])
            ->join('chat_inboxes', 'chat_conversations.inbox_id', '=', 'chat_inboxes.id')
            ->selectRaw('chat_inboxes.channel_type, COUNT(*) as count')
            ->groupBy('chat_inboxes.channel_type')
            ->get()
            ->mapWithKeys(fn ($item) => [class_basename($item->channel_type) => $item->count])
            ->toArray();
    }

    /**
     * Get conversation grouped by status (single join query).
     */
    public function getConversationsByStatus(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        return Conversation::query()
            ->where('chat_conversations.account_id', $accountId)
            ->whereBetween('chat_conversations.created_at', [$startDate, $endDate])
            ->join('chat_conversation_statuses as cs', 'chat_conversations.status_id', '=', 'cs.id')
            ->selectRaw('cs.slug as status, COUNT(*) as count')
            ->groupBy('cs.slug')
            ->get()
            ->mapWithKeys(fn ($item) => [ucfirst($item->status) => (int) $item->count])
            ->toArray();
    }

    /**
     * Get top performing agents by resolved conversations.
     *
     * Uses direct status_id column check instead of whereHas.
     */
    public function getTopAgents(int $accountId, Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        $resolvedId = $this->getStatusId($accountId, 'resolved');

        return Conversation::where('chat_conversations.account_id', $accountId)
            ->whereBetween('chat_conversations.created_at', [$startDate, $endDate])
            ->where('chat_conversations.status_id', $resolvedId)
            ->whereNotNull('chat_conversations.assignee_id')
            ->join('users', 'chat_conversations.assignee_id', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as resolved_count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resolved_count')
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn ($item) => [$item->name => $item->resolved_count])
            ->toArray();
    }

    /**
     * Get agent performance metrics.
     *
     * Collapses two count queries into one aggregation.
     */
    public function getAgentPerformance(int $accountId, int $agentId, Carbon $startDate, Carbon $endDate): array
    {
        $resolvedId = $this->getStatusId($accountId, 'resolved');

        $agg = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('assignee_id', $agentId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status_id = ? THEN 1 ELSE 0 END) as resolved', [$resolvedId])
            ->first();

        $total = (int) ($agg->total ?? 0);
        $resolved = (int) ($agg->resolved ?? 0);

        return [
            'total_assigned' => $total,
            'resolved' => $resolved,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            'avg_response_time' => $this->getAgentAverageResponseTime($accountId, $agentId, $startDate, $endDate),
        ];
    }

    /**
     * Get agent average response time.
     */
    protected function getAgentAverageResponseTime(int $accountId, int $agentId, Carbon $startDate, Carbon $endDate): float
    {
        $result = DB::table('chat_conversations as c')
            ->join('chat_conversation_messages as m1', function ($join) {
                $join->on('c.id', '=', 'm1.conversation_id')
                    ->where('m1.message_type', 'incoming')
                    ->whereRaw('m1.id = (
                        SELECT id FROM chat_conversation_messages
                        WHERE conversation_id = c.id
                        AND message_type = \'incoming\'
                        ORDER BY created_at ASC
                        LIMIT 1
                    )');
            })
            ->join('chat_conversation_messages as m2', function ($join) use ($agentId) {
                $join->on('c.id', '=', 'm2.conversation_id')
                    ->where('m2.message_type', 'outgoing')
                    ->where('m2.sender_type', User::class)
                    ->where('m2.sender_id', $agentId)
                    ->whereRaw('m2.id = (
                        SELECT id FROM chat_conversation_messages
                        WHERE conversation_id = c.id
                        AND message_type = \'outgoing\'
                        AND sender_type = ?
                        AND sender_id = ?
                        ORDER BY created_at ASC
                        LIMIT 1
                    )', [User::class, $agentId]);
            })
            ->where('c.account_id', $accountId)
            ->where('c.assignee_id', $agentId)
            ->whereBetween('c.created_at', [$startDate, $endDate])
            ->whereRaw('m2.created_at > m1.created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_seconds')
            ->first();

        return $result && $result->avg_seconds
            ? round($result->avg_seconds / 60, 2)
            : 0.0;
    }

    /**
     * Get busiest hours of the day.
     */
    public function getBusiestHours(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $data = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[sprintf('%02d:00', $i)] = (int) ($data[$i] ?? 0);
        }

        return $hours;
    }

    /**
     * Get message volume by day.
     *
     * Uses account_id directly on messages (no join needed).
     */
    public function getMessageVolumeByDay(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        return ConversationMessage::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(fn ($item) => [Carbon::parse($item->date)->format('M d') => $item->count])
            ->toArray();
    }

    /**
     * Export analytics data for a date range.
     */
    public function exportData(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'overview' => $this->getOverviewMetrics($accountId, $startDate, $endDate),
            'by_day' => $this->getConversationsByDay($accountId, $startDate, $endDate),
            'by_channel' => $this->getConversationsByChannel($accountId, $startDate, $endDate),
            'by_status' => $this->getConversationsByStatus($accountId, $startDate, $endDate),
            'top_agents' => $this->getTopAgents($accountId, $startDate, $endDate),
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Load all status slugs → IDs for a given account in one query, cached for 60 minutes.
     *
     * @return array<string, int>
     */
    protected function getStatusIds(int $accountId): array
    {
        return Cache::remember("conv_analytics:status_ids:{$accountId}", now()->addMinutes(60), function () use ($accountId) {
            return ConversationStatus::where('account_id', $accountId)
                ->pluck('id', 'slug')
                ->toArray();
        });
    }

    /**
     * Get the ID for a single status slug, or null if not found.
     */
    protected function getStatusId(int $accountId, string $slug): ?int
    {
        return $this->getStatusIds($accountId)[$slug] ?? null;
    }
}
