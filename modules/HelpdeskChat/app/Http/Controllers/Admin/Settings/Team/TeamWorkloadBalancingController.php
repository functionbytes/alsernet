<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Team;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Teams\Team;
use Modules\HelpdeskChat\Services\TeamWorkloadBalancingService;

class TeamWorkloadBalancingController extends Controller
{
    public function __construct(
        protected TeamWorkloadBalancingService $balancingService
    ) {}

    /**
     * Get workload distribution for all teams.
     */
    public function index(): JsonResponse
    {
        $accountId = auth()->user()->account_id;

        $workloads = $this->balancingService->getTeamWorkloads($accountId);

        return response()->json([
            'success' => true,
            'data' => $workloads,
        ]);
    }

    /**
     * Get agent workloads within a team.
     */
    public function agents(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $workloads = $this->balancingService->getAgentWorkloads($team->id);

        return response()->json([
            'success' => true,
            'data' => $workloads,
        ]);
    }

    /**
     * Rebalance conversation within a team.
     */
    public function rebalance(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'max_conversations_per_agent' => 'nullable|integer|min:1|max:50',
        ]);

        $result = $this->balancingService->rebalanceTeam(
            $team->id,
            $validated['max_conversations_per_agent'] ?? 15
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'reassigned' => $result['reassigned'],
            ],
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Get workload imbalance score for a team.
     */
    public function imbalanceScore(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $score = $this->balancingService->getImbalanceScore($team->id);

        return response()->json([
            'success' => true,
            'data' => [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'imbalance_score' => $score,
                'status' => match (true) {
                    $score > 75 => 'critical',
                    $score > 50 => 'high',
                    $score > 25 => 'moderate',
                    default => 'balanced',
                },
            ],
        ]);
    }

    /**
     * Get workload optimization recommendations.
     */
    public function recommendations(): JsonResponse
    {
        $accountId = auth()->user()->account_id;

        $recommendations = $this->balancingService->getRecommendations($accountId);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'count' => count($recommendations),
        ]);
    }
}
