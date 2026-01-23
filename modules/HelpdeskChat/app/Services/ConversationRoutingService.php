<?php

namespace Modules\HelpdeskChat\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationRoutingService
{
    // Routing strategies
    const STRATEGY_ROUND_ROBIN = 'round_robin';

    const STRATEGY_LEAST_BUSY = 'least_busy';

    const STRATEGY_BALANCED = 'balanced';

    const STRATEGY_SKILLED = 'skilled';

    const STRATEGY_AVAILABILITY = 'availability';

    /**
     * Auto-assign conversation to best available agent.
     */
    public function autoAssign(Conversation $conversation, ?string $strategy = null): ?User
    {
        $strategy = $strategy ?? self::STRATEGY_BALANCED;

        // Get available agents for this inbox
        $availableAgents = $this->getAvailableAgents($conversation->inbox);

        if ($availableAgents->isEmpty()) {
            return null;
        }

        // Apply routing strategy
        $selectedAgent = match ($strategy) {
            self::STRATEGY_ROUND_ROBIN => $this->roundRobinAssignment($conversation->inbox, $availableAgents),
            self::STRATEGY_LEAST_BUSY => $this->leastBusyAssignment($availableAgents),
            self::STRATEGY_BALANCED => $this->balancedAssignment($availableAgents),
            self::STRATEGY_SKILLED => $this->skilledAssignment($conversation, $availableAgents),
            self::STRATEGY_AVAILABILITY => $this->availabilityBasedAssignment($availableAgents),
            default => $this->balancedAssignment($availableAgents),
        };

        if ($selectedAgent) {
            $conversation->update(['assigned_agent_id' => $selectedAgent->id]);
        }

        return $selectedAgent;
    }

    /**
     * Get available agents for an inbox.
     */
    protected function getAvailableAgents(Inbox $inbox): Collection
    {
        $teamIds = $inbox->teams()->pluck('teams.id');

        if ($teamIds->isEmpty()) {
            // No teams assigned, get all agents in account
            return User::where('account_id', $inbox->account_id)
                ->where('is_active', true)
                ->get();
        }

        // Get agents from assigned teams
        return User::where('account_id', $inbox->account_id)
            ->where('is_active', true)
            ->whereHas('teams', function ($query) use ($teamIds) {
                $query->whereIn('teams.id', $teamIds);
            })
            ->get();
    }

    /**
     * Round robin assignment strategy.
     */
    protected function roundRobinAssignment(Inbox $inbox, Collection $agents): ?User
    {
        // Get last assigned agent for this inbox
        $lastAssignment = Conversation::where('inbox_id', $inbox->id)
            ->whereNotNull('assigned_agent_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastAssignment) {
            return $agents->first();
        }

        // Find next agent in rotation
        $currentIndex = $agents->search(function ($agent) use ($lastAssignment) {
            return $agent->id === $lastAssignment->assigned_agent_id;
        });

        if ($currentIndex === false) {
            return $agents->first();
        }

        $nextIndex = ($currentIndex + 1) % $agents->count();

        return $agents->get($nextIndex);
    }

    /**
     * Least busy assignment strategy.
     */
    protected function leastBusyAssignment(Collection $agents): ?User
    {
        $agentIds = $agents->pluck('id');

        // Count open conversation per agent
        $workloads = Conversation::whereIn('assigned_agent_id', $agentIds)
            ->whereIn('status', ['open', 'pending'])
            ->select('assigned_agent_id', DB::raw('COUNT(*) as conversation_count'))
            ->groupBy('assigned_agent_id')
            ->pluck('conversation_count', 'assigned_agent_id');

        // Find agent with least workload
        $leastBusyAgentId = $agents->sortBy(function ($agent) use ($workloads) {
            return $workloads->get($agent->id, 0);
        })->first()->id;

        return $agents->firstWhere('id', $leastBusyAgentId);
    }

    /**
     * Balanced assignment strategy (considers both workload and availability).
     */
    protected function balancedAssignment(Collection $agents): ?User
    {
        $agentIds = $agents->pluck('id');

        // Get workload and performance metrics
        $metrics = Conversation::whereIn('assigned_agent_id', $agentIds)
            ->whereIn('status', ['open', 'pending'])
            ->select(
                'assigned_agent_id',
                DB::raw('COUNT(*) as active_conversations'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(last_activity_at, NOW()))) as avg_response_time')
            )
            ->groupBy('assigned_agent_id')
            ->get()
            ->keyBy('assigned_agent_id');

        // Calculate scores (lower is better)
        $scores = $agents->map(function ($agent) use ($metrics) {
            $metric = $metrics->get($agent->id);

            $activeConversations = $metric->active_conversations ?? 0;
            $avgResponseTime = $metric->avg_response_time ?? 0;

            // Weight: 70% workload, 30% response time
            return [
                'agent' => $agent,
                'score' => ($activeConversations * 0.7) + (($avgResponseTime / 60) * 0.3),
            ];
        })->sortBy('score');

        return $scores->first()['agent'] ?? $agents->first();
    }

    /**
     * Skilled assignment strategy (based on labels/tags).
     */
    protected function skilledAssignment(Conversation $conversation, Collection $agents): ?User
    {
        // Get conversation labels
        $conversationLabels = $conversation->labels->pluck('id');

        if ($conversationLabels->isEmpty()) {
            return $this->balancedAssignment($agents);
        }

        // Find agents with matching expertise (based on successfully resolved conversation with same labels)
        $agentScores = $agents->map(function ($agent) use ($conversationLabels) {
            $matchingResolved = Conversation::where('assigned_agent_id', $agent->id)
                ->where('status', 'resolved')
                ->whereHas('labels', function ($query) use ($conversationLabels) {
                    $query->whereIn('labels.id', $conversationLabels);
                })
                ->count();

            return [
                'agent' => $agent,
                'expertise_score' => $matchingResolved,
            ];
        })->sortByDesc('expertise_score');

        $topScoringAgent = $agentScores->first();

        if ($topScoringAgent && $topScoringAgent['expertise_score'] > 0) {
            return $topScoringAgent['agent'];
        }

        // Fallback to balanced assignment
        return $this->balancedAssignment($agents);
    }

    /**
     * Availability-based assignment (online agents first).
     */
    protected function availabilityBasedAssignment(Collection $agents): ?User
    {
        // Get online agents (last seen within 5 minutes)
        $onlineAgents = $agents->filter(function ($agent) {
            return $agent->last_seen_at && $agent->last_seen_at->diffInMinutes(now()) <= 5;
        });

        if ($onlineAgents->isNotEmpty()) {
            return $this->leastBusyAssignment($onlineAgents);
        }

        // Fallback to least busy if no one is online
        return $this->leastBusyAssignment($agents);
    }

    /**
     * Get routing statistics.
     */
    public function getRoutingStats(int $accountId, ?int $inboxId = null): array
    {
        $query = Conversation::where('account_id', $accountId)
            ->whereNotNull('assigned_agent_id');

        if ($inboxId) {
            $query->where('inbox_id', $inboxId);
        }

        $total = $query->count();
        $autoAssigned = $query->where('auto_assigned', true)->count();

        $avgAssignmentTime = Conversation::where('account_id', $accountId)
            ->whereNotNull('assigned_agent_id')
            ->whereNotNull('first_assigned_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_assigned_at)) as avg_time'))
            ->value('avg_time');

        return [
            'total_assigned' => $total,
            'auto_assigned' => $autoAssigned,
            'manual_assigned' => $total - $autoAssigned,
            'auto_assignment_rate' => $total > 0 ? round(($autoAssigned / $total) * 100, 2) : 0,
            'avg_assignment_time_seconds' => round($avgAssignmentTime ?? 0, 2),
        ];
    }

    /**
     * Suggest best agent for manual assignment.
     */
    public function suggestAgent(Conversation $conversation): ?array
    {
        $availableAgents = $this->getAvailableAgents($conversation->inbox);

        if ($availableAgents->isEmpty()) {
            return null;
        }

        $agent = $this->balancedAssignment($availableAgents);

        if (! $agent) {
            return null;
        }

        // Get agent workload
        $activeConversations = Conversation::where('assigned_agent_id', $agent->id)
            ->whereIn('status', ['open', 'pending'])
            ->count();

        return [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'agent_email' => $agent->email,
            'active_conversations' => $activeConversations,
            'reason' => 'Best available based on current workload and performance',
        ];
    }
}
