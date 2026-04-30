<?php

namespace Modules\Chat\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Inbox\Inbox;

class ConversationRoutingService
{
    // Routing strategies
    const STRATEGY_ROUND_ROBIN = 'round_robin';

    const STRATEGY_LEAST_BUSY = 'least_busy';

    const STRATEGY_BALANCED = 'balanced';

    const STRATEGY_SKILLED = 'skilled';

    const STRATEGY_AVAILABILITY = 'availability';

    /**
     * Auto-assign a conversation to the best available agent.
     *
     * Selects an agent based on the specified strategy and updates the conversation's
     * assignee_id. Returns null if no available agents found.
     *
     * @param  Conversation  $conversation  The conversation to assign
     * @param  string|null  $strategy  Routing strategy to use (defaults to STRATEGY_BALANCED)
     * @return User|null The selected agent, or null if no agents available
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
            $conversation->update(['assignee_id' => $selectedAgent->id]);
        }

        return $selectedAgent;
    }

    /**
     * Get all active agents available for an inbox.
     *
     * If the inbox has assigned teams, returns agents from those teams.
     * Otherwise returns all active agents in the account.
     *
     * @param  Inbox  $inbox  The inbox to get agents for
     * @return Collection Active users eligible for assignment to this inbox
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
     * Round-robin assignment strategy.
     *
     * Cycles through agents in order, tracking the last assigned agent for the inbox.
     * Ensures even distribution of new conversations over time.
     *
     * @param  Inbox  $inbox  The inbox context for last assignment tracking
     * @param  Collection  $agents  Available agents to rotate through
     * @return User|null The next agent in rotation, or first agent if list is empty
     */
    protected function roundRobinAssignment(Inbox $inbox, Collection $agents): ?User
    {
        // Get last assigned agent for this inbox
        $lastAssignment = Conversation::where('inbox_id', $inbox->id)
            ->whereNotNull('assignee_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastAssignment) {
            return $agents->first();
        }

        // Find next agent in rotation
        $currentIndex = $agents->search(function ($agent) use ($lastAssignment) {
            return $agent->id === $lastAssignment->assignee_id;
        });

        if ($currentIndex === false) {
            return $agents->first();
        }

        $nextIndex = ($currentIndex + 1) % $agents->count();

        return $agents->get($nextIndex);
    }

    /**
     * Least busy assignment strategy.
     *
     * Selects the agent with the fewest open or pending conversations.
     * Simple workload-based distribution.
     *
     * @param  Collection  $agents  Available agents to choose from
     * @return User|null The agent with the lowest open conversation count
     */
    protected function leastBusyAssignment(Collection $agents): ?User
    {
        $agentIds = $agents->pluck('id');

        // Count open conversation per agent
        $workloads = Conversation::whereIn('assignee_id', $agentIds)
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['open', 'pending']))
            ->select('assignee_id', DB::raw('COUNT(*) as conversation_count'))
            ->groupBy('assignee_id')
            ->pluck('conversation_count', 'assignee_id');

        // Find agent with least workload
        $leastBusyAgentId = $agents->sortBy(function ($agent) use ($workloads) {
            return $workloads->get($agent->id, 0);
        })->first()->id;

        return $agents->firstWhere('id', $leastBusyAgentId);
    }

    /**
     * Balanced assignment strategy.
     *
     * Considers both current workload (70% weight) and average response time (30% weight).
     * Selects agent with the lowest combined score.
     *
     * @param  Collection  $agents  Available agents to choose from
     * @return User|null The agent with the best balanced score
     */
    protected function balancedAssignment(Collection $agents): ?User
    {
        $agentIds = $agents->pluck('id');

        // Get workload and performance metrics
        $metrics = Conversation::whereIn('assignee_id', $agentIds)
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['open', 'pending']))
            ->select(
                'assignee_id',
                DB::raw('COUNT(*) as active_conversations'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(last_activity_at, NOW()))) as avg_response_time')
            )
            ->groupBy('assignee_id')
            ->get()
            ->keyBy('assignee_id');

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
     * Skilled assignment strategy.
     *
     * Assigns to agent with most expertise on conversation's labels.
     * Counts resolved conversations with matching labels per agent.
     * Falls back to balanced assignment if no labels or no expert found.
     *
     * @param  Conversation  $conversation  The conversation with labels to match
     * @param  Collection  $agents  Available agents to choose from
     * @return User|null The agent with highest expertise on the labels, or null
     */
    protected function skilledAssignment(Conversation $conversation, Collection $agents): ?User
    {
        // Ensure conversation has labels loaded
        if (! $conversation->relationLoaded('labels')) {
            $conversation->load('labels');
        }

        // Get conversation labels
        $conversationLabels = $conversation->labels->pluck('id');

        if ($conversationLabels->isEmpty()) {
            return $this->balancedAssignment($agents);
        }

        // Batch query: count resolved conversations with matching labels per agent
        $expertiseCounts = Conversation::whereIn('assignee_id', $agents->pluck('id'))
            ->whereHas('status', fn ($q) => $q->where('slug', 'resolved'))
            ->whereHas('labels', fn ($query) => $query->whereIn('labels.id', $conversationLabels))
            ->select('assignee_id', DB::raw('COUNT(*) as resolved_count'))
            ->groupBy('assignee_id')
            ->pluck('resolved_count', 'assignee_id');

        $agentScores = $agents->map(fn ($agent) => [
            'agent' => $agent,
            'expertise_score' => $expertiseCounts->get($agent->id, 0),
        ])->sortByDesc('expertise_score');

        $topScoringAgent = $agentScores->first();

        if ($topScoringAgent && $topScoringAgent['expertise_score'] > 0) {
            return $topScoringAgent['agent'];
        }

        // Fallback to balanced assignment
        return $this->balancedAssignment($agents);
    }

    /**
     * Availability-based assignment strategy.
     *
     * Prioritizes agents who have been active in the last 5 minutes.
     * Among online agents, uses least-busy logic. Falls back to least-busy
     * across all agents if no one is currently active.
     *
     * @param  Collection  $agents  Available agents to choose from
     * @return User|null The best available agent, or null if no agents
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
     * Get routing statistics for the account or inbox.
     *
     * Returns counts of total assigned conversations, auto-assigned vs manual,
     * auto-assignment rate percentage, and average time to assignment in seconds.
     *
     * @param  int  $accountId  The account to get statistics for
     * @param  int|null  $inboxId  Optional inbox to filter by
     * @return array{total_assigned: int, auto_assigned: int, manual_assigned: int,
     *               auto_assignment_rate: float, avg_assignment_time_seconds: float}
     */
    public function getRoutingStats(int $accountId, ?int $inboxId = null): array
    {
        $query = Conversation::where('account_id', $accountId)
            ->whereNotNull('assignee_id');

        if ($inboxId) {
            $query->where('inbox_id', $inboxId);
        }

        $total = $query->count();
        $autoAssigned = (clone $query)->where('auto_assigned', true)->count();

        $avgAssignmentTime = Conversation::where('account_id', $accountId)
            ->whereNotNull('assignee_id')
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
     * Suggest the best agent for manual assignment.
     *
     * Returns recommended agent based on balanced scoring algorithm, along with
     * agent details and current workload info for informing manual assignment decisions.
     *
     * @param  Conversation  $conversation  The conversation to suggest an agent for
     * @return array{agent_id: int, agent_name: string, agent_email: string,
     *               active_conversations: int, reason: string}|null Agent suggestion or null
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
        $activeConversations = Conversation::where('assignee_id', $agent->id)
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['open', 'pending']))
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
