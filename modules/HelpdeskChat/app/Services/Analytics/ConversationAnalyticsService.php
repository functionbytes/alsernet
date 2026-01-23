<?php

namespace Modules\HelpdeskChat\Services\Analytics;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class ConversationAnalyticsService
{
    /**
     * Get conversation overview metrics for a date range.
     */
    public function getOverviewMetrics(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $totalConversations = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $resolvedConversations = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'resolved')
            ->count();

        $avgResponseTime = $this->getAverageResponseTime($accountId, $startDate, $endDate);
        $avgResolutionTime = $this->getAverageResolutionTime($accountId, $startDate, $endDate);

        $resolutionRate = $totalConversations > 0
            ? round(($resolvedConversations / $totalConversations) * 100, 2)
            : 0;

        return [
            'total_conversations' => $totalConversations,
            'resolved_conversations' => $resolvedConversations,
            'open_conversations' => $totalConversations - $resolvedConversations,
            'resolution_rate' => $resolutionRate,
            'avg_response_time' => $avgResponseTime,
            'avg_resolution_time' => $avgResolutionTime,
        ];
    }

    /**
     * Get average first response time in minutes.
     */
    protected function getAverageResponseTime(int $accountId, Carbon $startDate, Carbon $endDate): float
    {
        $result = DB::table('helpdesk_conversations as c')
            ->join('helpdesk_conversation_messages as m1', function ($join) {
                $join->on('c.id', '=', 'm1.conversation_id')
                    ->where('m1.message_type', 'incoming')
                    ->whereRaw('m1.id = (
                        SELECT id FROM helpdesk_conversation_messages
                        WHERE conversation_id = c.id
                        AND message_type = \'incoming\'
                        ORDER BY created_at ASC
                        LIMIT 1
                    )');
            })
            ->join('helpdesk_conversation_messages as m2', function ($join) {
                $join->on('c.id', '=', 'm2.conversation_id')
                    ->where('m2.message_type', 'outgoing')
                    ->where('m2.sender_type', User::class)
                    ->whereRaw('m2.id = (
                        SELECT id FROM helpdesk_conversation_messages
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
     * Get average resolution time in hours.
     */
    protected function getAverageResolutionTime(int $accountId, Carbon $startDate, Carbon $endDate): float
    {
        $result = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'resolved')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds')
            ->first();

        return $result && $result->avg_seconds
            ? round($result->avg_seconds / 3600, 2)
            : 0.0;
    }

    /**
     * Get conversation grouped by day.
     */
    public function getConversationsByDay(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $conversations->mapWithKeys(fn ($item) => [
            Carbon::parse($item->date)->format('M d') => $item->count,
        ])->toArray();
    }

    /**
     * Get conversation grouped by channel.
     */
    public function getConversationsByChannel(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = Conversation::where('helpdesk_conversations.account_id', $accountId)
            ->whereBetween('helpdesk_conversations.created_at', [$startDate, $endDate])
            ->join('helpdesk_inboxes', 'helpdesk_conversations.inbox_id', '=', 'helpdesk_inboxes.id')
            ->selectRaw('helpdesk_inboxes.channel_type, COUNT(*) as count')
            ->groupBy('helpdesk_inboxes.channel_type')
            ->get();

        return $conversations->mapWithKeys(function ($item) {
            $channelName = class_basename($item->channel_type);

            return [$channelName => $item->count];
        })->toArray();
    }

    /**
     * Get conversation grouped by status.
     */
    public function getConversationsByStatus(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $conversations = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return $conversations->mapWithKeys(fn ($item) => [
            ucfirst($item->status) => $item->count,
        ])->toArray();
    }

    /**
     * Get top performing agents by resolved conversation.
     */
    public function getTopAgents(int $accountId, Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return Conversation::where('helpdesk_conversations.account_id', $accountId)
            ->whereBetween('helpdesk_conversations.created_at', [$startDate, $endDate])
            ->where('helpdesk_conversations.status', 'resolved')
            ->whereNotNull('helpdesk_conversations.assignee_id')
            ->join('users', 'helpdesk_conversations.assignee_id', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as resolved_count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('resolved_count')
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->name => $item->resolved_count,
            ])
            ->toArray();
    }

    /**
     * Get agent performance metrics.
     */
    public function getAgentPerformance(int $accountId, int $agentId, Carbon $startDate, Carbon $endDate): array
    {
        $totalAssigned = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('assignee_id', $agentId)
            ->count();

        $resolved = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('assignee_id', $agentId)
            ->where('status', 'resolved')
            ->count();

        $avgResponseTime = $this->getAgentAverageResponseTime($accountId, $agentId, $startDate, $endDate);

        $resolutionRate = $totalAssigned > 0
            ? round(($resolved / $totalAssigned) * 100, 2)
            : 0;

        return [
            'total_assigned' => $totalAssigned,
            'resolved' => $resolved,
            'resolution_rate' => $resolutionRate,
            'avg_response_time' => $avgResponseTime,
        ];
    }

    /**
     * Get agent average response time.
     */
    protected function getAgentAverageResponseTime(int $accountId, int $agentId, Carbon $startDate, Carbon $endDate): float
    {
        $result = DB::table('helpdesk_conversations as c')
            ->join('helpdesk_conversation_messages as m1', function ($join) {
                $join->on('c.id', '=', 'm1.conversation_id')
                    ->where('m1.message_type', 'incoming')
                    ->whereRaw('m1.id = (
                        SELECT id FROM helpdesk_conversation_messages
                        WHERE conversation_id = c.id
                        AND message_type = \'incoming\'
                        ORDER BY created_at ASC
                        LIMIT 1
                    )');
            })
            ->join('helpdesk_conversation_messages as m2', function ($join) use ($agentId) {
                $join->on('c.id', '=', 'm2.conversation_id')
                    ->where('m2.message_type', 'outgoing')
                    ->where('m2.sender_type', User::class)
                    ->where('m2.sender_id', $agentId)
                    ->whereRaw('m2.id = (
                        SELECT id FROM helpdesk_conversation_messages
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
        $conversations = Conversation::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[sprintf('%02d:00', $i)] = 0;
        }

        foreach ($conversations as $conv) {
            $hours[sprintf('%02d:00', $conv->hour)] = $conv->count;
        }

        return $hours;
    }

    /**
     * Get message volume by day.
     */
    public function getMessageVolumeByDay(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $messages = ConversationMessage::where('account_id', $accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $messages->mapWithKeys(fn ($item) => [
            Carbon::parse($item->date)->format('M d') => $item->count,
        ])->toArray();
    }

    /**
     * Export analytics data for a date range.
     */
    public function exportData(int $accountId, Carbon $startDate, Carbon $endDate): array
    {
        $overview = $this->getOverviewMetrics($accountId, $startDate, $endDate);
        $byDay = $this->getConversationsByDay($accountId, $startDate, $endDate);
        $byChannel = $this->getConversationsByChannel($accountId, $startDate, $endDate);
        $byStatus = $this->getConversationsByStatus($accountId, $startDate, $endDate);
        $topAgents = $this->getTopAgents($accountId, $startDate, $endDate);

        return [
            'overview' => $overview,
            'by_day' => $byDay,
            'by_channel' => $byChannel,
            'by_status' => $byStatus,
            'top_agents' => $topAgents,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }
}
