<?php

namespace Modules\Chat\Services\Conversations;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Teams\Team;

class ConversationAssignmentService
{
    /**
     * Auto-assign a conversation using round-robin algorithm.
     *
     * Skips if conversation already assigned. Gets team from inbox and checks if
     * auto-assignment is enabled. Filters to agents with conversation.view permission.
     * Uses cached round-robin state to ensure even distribution.
     *
     * @param  Conversation  $conversation  The conversation to auto-assign
     * @return User|null The assigned agent, or null if no available agents or auto-assign disabled
     */
    public function autoAssign(Conversation $conversation): ?User
    {
        // If conversation already has an assignee, skip
        if ($conversation->assignee_id) {
            return $conversation->assignee;
        }

        // Ensure inbox and team are loaded
        if (! $conversation->relationLoaded('inbox')) {
            $conversation->load('inbox.team');
        } elseif (! $conversation->inbox->relationLoaded('team')) {
            $conversation->inbox->load('team');
        }

        // Get team for this conversation's inbox
        $team = $conversation->inbox->team;

        if (! $team || ! $team->allow_auto_assign) {
            return null;
        }

        // Get available agents from team
        $availableAgents = $this->getAvailableAgents($team);

        if ($availableAgents->isEmpty()) {
            Log::warning('No available agents for auto-assignment', [
                'conversation_id' => $conversation->id,
                'team_id' => $team->id,
            ]);

            return null;
        }

        // Select agent using round-robin
        $selectedAgent = $this->selectAgentRoundRobin($team, $availableAgents);

        // Assign conversation
        $conversation->update(['assignee_id' => $selectedAgent->id]);

        Log::info('Conversation auto-assigned', [
            'conversation_id' => $conversation->id,
            'assignee_id' => $selectedAgent->id,
            'team_id' => $team->id,
        ]);

        return $selectedAgent;
    }

    /**
     * Get available team members with conversation.view permission.
     *
     * Filters team members by online/busy availability status and permission check.
     *
     * @param  Team  $team  The team to get available members from
     * @return Collection Available users with permission
     */
    protected function getAvailableAgents(Team $team)
    {
        return $team->members()
            ->whereIn('availability_status', ['online', 'busy'])
            ->with('teamRole')
            ->get()
            ->filter(function ($user) {
                // Filter agents who have permission to view conversation
                return $user->hasPermission('conversation.view');
            });
    }

    /**
     * Select next agent using round-robin algorithm.
     *
     * Uses cached rotation index to ensure even distribution across team.
     * Index persists for 24 hours. Agent IDs sorted for consistent ordering.
     *
     * @param  Team  $team  The team context for caching
     * @param  Collection  $availableAgents  Available agents to rotate through
     * @return User The next agent in rotation
     */
    protected function selectAgentRoundRobin(Team $team, $availableAgents): User
    {
        $cacheKey = "team:{$team->id}:last_assigned_agent";

        // Get last assigned agent index
        $lastIndex = Cache::get($cacheKey, -1);

        // Get agent IDs in consistent order
        $agentIds = $availableAgents->pluck('id')->sort()->values();

        // Calculate next index (round-robin)
        $nextIndex = ($lastIndex + 1) % $agentIds->count();

        // Get selected agent ID
        $selectedAgentId = $agentIds[$nextIndex];

        // Update cache
        Cache::put($cacheKey, $nextIndex, now()->addHours(24));

        return $availableAgents->firstWhere('id', $selectedAgentId);
    }

    /**
     * Manually assign a conversation to a specific agent.
     *
     * Validates that agent belongs to the conversation's team.
     * Logs warnings if validation fails but returns false instead of throwing.
     *
     * @param  Conversation  $conversation  The conversation to assign
     * @param  User  $agent  The agent to assign to
     * @return bool True if assignment successful, false if validation fails
     */
    public function assignToAgent(Conversation $conversation, User $agent): bool
    {
        // Verify agent belongs to conversation's team
        $team = $conversation->inbox->team;

        if ($team && ! $team->hasMember($agent)) {
            Log::warning('Agent not in team for conversation assignment', [
                'conversation_id' => $conversation->id,
                'agent_id' => $agent->id,
                'team_id' => $team->id,
            ]);

            return false;
        }

        $conversation->update(['assignee_id' => $agent->id]);

        Log::info('Conversation manually assigned', [
            'conversation_id' => $conversation->id,
            'assignee_id' => $agent->id,
        ]);

        return true;
    }

    /**
     * Remove assignment from a conversation.
     *
     * @param  Conversation  $conversation  The conversation to unassign
     */
    public function unassign(Conversation $conversation): void
    {
        $conversation->update(['assignee_id' => null]);

        Log::info('Conversation unassigned', [
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Reassign all open conversations from an offline agent.
     *
     * Gets all open conversations assigned to the agent and attempts auto-assignment
     * to other available agents. Returns count of successfully reassigned conversations.
     *
     * @param  User  $agent  The agent going offline
     * @return int Number of conversations successfully reassigned
     */
    public function reassignFromOfflineAgent(User $agent): int
    {
        $reassignedCount = 0;

        // Get all open conversation assigned to this agent
        $conversations = Conversation::where('assignee_id', $agent->id)
            ->whereHas('status', fn ($q) => $q->where('slug', 'open'))
            ->with('inbox.team')
            ->get();

        foreach ($conversations as $conversation) {
            // Try to auto-assign to another available agent
            if ($this->autoAssign($conversation)) {
                $reassignedCount++;
            }
        }

        Log::info('Reassigned conversation from offline agent', [
            'agent_id' => $agent->id,
            'reassigned_count' => $reassignedCount,
        ]);

        return $reassignedCount;
    }

    /**
     * Balance open conversation workload across team members.
     *
     * Calculates average workload and redistributes conversations from overloaded agents
     * (>2 above average) to underloaded agents. Uses oldest conversations first.
     * Returns summary of rebalancing actions.
     *
     * @param  Team  $team  The team to balance workload for
     * @return array{message: string, rebalanced_count?: int, workload?: array} Rebalancing summary
     */
    public function balanceWorkload(Team $team): array
    {
        $availableAgents = $this->getAvailableAgents($team);

        if ($availableAgents->count() < 2) {
            return ['message' => 'Not enough available agents for balancing'];
        }

        // Get workload per agent
        $workload = [];
        foreach ($availableAgents as $agent) {
            $workload[$agent->id] = Conversation::where('assignee_id', $agent->id)
                ->whereHas('status', fn ($q) => $q->where('slug', 'open'))
                ->count();
        }

        $avgWorkload = array_sum($workload) / count($workload);
        $rebalancedCount = 0;

        // Find agents with above average workload
        foreach ($workload as $agentId => $count) {
            if ($count > $avgWorkload + 2) {
                $agent = $availableAgents->firstWhere('id', $agentId);
                $excessConversations = Conversation::where('assignee_id', $agentId)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'open'))
                    ->orderBy('created_at', 'desc')
                    ->limit((int) ($count - $avgWorkload))
                    ->get();

                foreach ($excessConversations as $conversation) {
                    // Find agent with lowest workload
                    $targetAgentId = array_search(min($workload), $workload);
                    $targetAgent = $availableAgents->firstWhere('id', $targetAgentId);

                    if ($targetAgent && $this->assignToAgent($conversation, $targetAgent)) {
                        $workload[$targetAgentId]++;
                        $workload[$agentId]--;
                        $rebalancedCount++;
                    }
                }
            }
        }

        return [
            'message' => 'Workload balanced successfully',
            'rebalanced_count' => $rebalancedCount,
            'workload' => $workload,
        ];
    }
}
