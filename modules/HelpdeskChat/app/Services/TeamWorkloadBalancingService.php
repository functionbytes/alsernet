<?php

namespace Modules\HelpdeskChat\Services;

use App\Models\Teams\Team;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class TeamWorkloadBalancingService
{
    /**
     * Get workload distribution for all teams.
     */
    public function getTeamWorkloads(int $accountId): array
    {
        $teams = Team::where('account_id', $accountId)
            ->with('members')
            ->get();

        return $teams->map(function ($team) {
            $workload = $this->getTeamWorkload($team);

            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'total_agents' => $team->members->count(),
                'active_agents' => $team->members->where('is_active', true)->count(),
                'total_conversations' => $workload['total'],
                'open_conversations' => $workload['open'],
                'pending_conversations' => $workload['pending'],
                'avg_conversations_per_agent' => $workload['avg_per_agent'],
                'workload_status' => $workload['status'],
            ];
        })->toArray();
    }

    /**
     * Get workload for a specific team.
     */
    protected function getTeamWorkload(Team $team): array
    {
        $agentIds = $team->members->pluck('id');
        $activeAgents = $team->members->where('is_active', true)->count();

        if ($activeAgents === 0) {
            return [
                'total' => 0,
                'open' => 0,
                'pending' => 0,
                'avg_per_agent' => 0,
                'status' => 'inactive',
            ];
        }

        $conversations = Conversation::whereIn('assigned_agent_id', $agentIds)
            ->whereIn('status', ['open', 'pending'])
            ->get();

        $total = $conversations->count();
        $open = $conversations->where('status', 'open')->count();
        $pending = $conversations->where('status', 'pending')->count();
        $avgPerAgent = round($total / $activeAgents, 2);

        // Determine status
        $status = match (true) {
            $avgPerAgent > 20 => 'overloaded',
            $avgPerAgent > 10 => 'busy',
            $avgPerAgent > 5 => 'normal',
            default => 'light',
        };

        return [
            'total' => $total,
            'open' => $open,
            'pending' => $pending,
            'avg_per_agent' => $avgPerAgent,
            'status' => $status,
        ];
    }

    /**
     * Get agent workloads within a team.
     */
    public function getAgentWorkloads(int $teamId): array
    {
        $team = Team::with('members')->findOrFail($teamId);

        $agentIds = $team->members->pluck('id');

        $workloads = Conversation::whereIn('assigned_agent_id', $agentIds)
            ->whereIn('status', ['open', 'pending'])
            ->select(
                'assigned_agent_id',
                DB::raw('COUNT(*) as total_conversations'),
                DB::raw('SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_conversations'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_conversations'),
                DB::raw('MAX(created_at) as latest_assignment')
            )
            ->groupBy('assigned_agent_id')
            ->get()
            ->keyBy('assigned_agent_id');

        return $team->members->map(function ($agent) use ($workloads) {
            $workload = $workloads->get($agent->id);

            $totalConversations = $workload->total_conversations ?? 0;

            return [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'agent_email' => $agent->email,
                'is_active' => $agent->is_active,
                'total_conversations' => $totalConversations,
                'open_conversations' => $workload->open_conversations ?? 0,
                'pending_conversations' => $workload->pending_conversations ?? 0,
                'latest_assignment' => $workload->latest_assignment ?? null,
                'workload_level' => $this->getWorkloadLevel($totalConversations),
            ];
        })->toArray();
    }

    /**
     * Determine workload level.
     */
    protected function getWorkloadLevel(int $conversationCount): string
    {
        return match (true) {
            $conversationCount > 20 => 'overloaded',
            $conversationCount > 10 => 'busy',
            $conversationCount > 5 => 'normal',
            $conversationCount > 0 => 'light',
            default => 'idle',
        };
    }

    /**
     * Rebalance conversation within a team.
     */
    public function rebalanceTeam(int $teamId, int $maxConversationsPerAgent = 15): array
    {
        $team = Team::with('members')->findOrFail($teamId);
        $activeAgents = $team->members->where('is_active', true);

        if ($activeAgents->count() < 2) {
            return [
                'success' => false,
                'message' => 'Need at least 2 active agents to rebalance',
                'reassigned' => 0,
            ];
        }

        // Get current workloads
        $agentWorkloads = collect($this->getAgentWorkloads($teamId))
            ->where('is_active', true)
            ->sortByDesc('total_conversations');

        $overloadedAgents = $agentWorkloads->where('total_conversations', '>', $maxConversationsPerAgent);

        if ($overloadedAgents->isEmpty()) {
            return [
                'success' => true,
                'message' => 'Team workload is already balanced',
                'reassigned' => 0,
            ];
        }

        $reassignedCount = 0;

        DB::transaction(function () use ($overloadedAgents, $activeAgents, $maxConversationsPerAgent, &$reassignedCount) {
            foreach ($overloadedAgents as $overloadedAgent) {
                $excessConversations = $overloadedAgent['total_conversations'] - $maxConversationsPerAgent;

                if ($excessConversations <= 0) {
                    continue;
                }

                // Get pending conversation to reassign (prioritize pending over open)
                $conversationsToReassign = Conversation::where('assigned_agent_id', $overloadedAgent['agent_id'])
                    ->whereIn('status', ['pending', 'open'])
                    ->orderBy(DB::raw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END"))
                    ->orderBy('created_at', 'asc')
                    ->limit($excessConversations)
                    ->get();

                foreach ($conversationsToReassign as $conversation) {
                    // Find least busy agent (excluding current agent)
                    $targetAgent = $activeAgents
                        ->where('id', '!=', $overloadedAgent['agent_id'])
                        ->sortBy(function ($agent) {
                            return Conversation::where('assigned_agent_id', $agent->id)
                                ->whereIn('status', ['open', 'pending'])
                                ->count();
                        })
                        ->first();

                    if ($targetAgent) {
                        $conversation->update(['assigned_agent_id' => $targetAgent->id]);
                        $reassignedCount++;
                    }
                }
            }
        });

        return [
            'success' => true,
            'message' => "Rebalanced {$reassignedCount} conversation",
            'reassigned' => $reassignedCount,
        ];
    }

    /**
     * Get workload imbalance score (0 = balanced, higher = more imbalanced).
     */
    public function getImbalanceScore(int $teamId): float
    {
        $workloads = collect($this->getAgentWorkloads($teamId))
            ->where('is_active', true)
            ->pluck('total_conversations');

        if ($workloads->isEmpty()) {
            return 0;
        }

        $mean = $workloads->avg();

        if ($mean == 0) {
            return 0;
        }

        // Calculate coefficient of variation (standard deviation / mean)
        $variance = $workloads->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();

        $stdDev = sqrt($variance);

        return round(($stdDev / $mean) * 100, 2);
    }

    /**
     * Get recommendations for workload optimization.
     */
    public function getRecommendations(int $accountId): array
    {
        $recommendations = [];

        $teamWorkloads = collect($this->getTeamWorkloads($accountId));

        // Check for overloaded teams
        $overloadedTeams = $teamWorkloads->where('workload_status', 'overloaded');

        foreach ($overloadedTeams as $team) {
            $recommendations[] = [
                'type' => 'overloaded_team',
                'severity' => 'high',
                'team_id' => $team['team_id'],
                'team_name' => $team['team_name'],
                'message' => "Team {$team['team_name']} is overloaded with {$team['avg_conversations_per_agent']} conversation per agent on average.",
                'action' => 'Consider adding more agents or redistributing conversation.',
            ];
        }

        // Check for idle agents
        foreach ($teamWorkloads as $teamWorkload) {
            $agentWorkloads = collect($this->getAgentWorkloads($teamWorkload['team_id']));
            $idleAgents = $agentWorkloads->where('workload_level', 'idle')->where('is_active', true);

            if ($idleAgents->count() > 0) {
                $recommendations[] = [
                    'type' => 'idle_agents',
                    'severity' => 'medium',
                    'team_id' => $teamWorkload['team_id'],
                    'team_name' => $teamWorkload['team_name'],
                    'message' => "{$idleAgents->count()} agent(s) in {$teamWorkload['team_name']} have no active conversation.",
                    'action' => 'Consider adjusting routing rules or redistributing workload.',
                ];
            }
        }

        // Check for high imbalance
        foreach ($teamWorkloads as $teamWorkload) {
            $imbalanceScore = $this->getImbalanceScore($teamWorkload['team_id']);

            if ($imbalanceScore > 50) {
                $recommendations[] = [
                    'type' => 'high_imbalance',
                    'severity' => 'medium',
                    'team_id' => $teamWorkload['team_id'],
                    'team_name' => $teamWorkload['team_name'],
                    'message' => "High workload imbalance detected in {$teamWorkload['team_name']} (score: {$imbalanceScore}).",
                    'action' => 'Run team rebalancing to distribute conversation more evenly.',
                ];
            }
        }

        return $recommendations;
    }
}
