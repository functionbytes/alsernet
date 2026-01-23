<?php

namespace Modules\HelpdeskChat\Services\Conversations;

use App\Models\Teams\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ConversationAssignmentService
{
    /**
     * Auto-assign conversation using round-robin.
     */
    public function autoAssign(Conversation $conversation): ?User
    {
        // If conversation already has an assignee, skip
        if ($conversation->assignee_id) {
            return $conversation->assignee;
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
     * Get available agents from team.
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
     * Assign conversation to specific agent.
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
     * Unassign conversation.
     */
    public function unassign(Conversation $conversation): void
    {
        $conversation->update(['assignee_id' => null]);

        Log::info('Conversation unassigned', [
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Reassign conversation when agent goes offline.
     */
    public function reassignFromOfflineAgent(User $agent): int
    {
        $reassignedCount = 0;

        // Get all open conversation assigned to this agent
        $conversations = Conversation::where('assignee_id', $agent->id)
            ->where('status', 'open')
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
     * Balance workload across team members.
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
                ->where('status', 'open')
                ->count();
        }

        $avgWorkload = array_sum($workload) / count($workload);
        $rebalancedCount = 0;

        // Find agents with above average workload
        foreach ($workload as $agentId => $count) {
            if ($count > $avgWorkload + 2) {
                $agent = $availableAgents->firstWhere('id', $agentId);
                $excessConversations = Conversation::where('assignee_id', $agentId)
                    ->where('status', 'open')
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
