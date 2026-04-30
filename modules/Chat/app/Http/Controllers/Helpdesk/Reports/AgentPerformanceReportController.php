<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Reports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Services\Analytics\AgentPerformanceService;
use Modules\Chat\Services\Reports\AgentPerformanceExportService;

class AgentPerformanceReportController extends Controller
{
    /**
     * Display the agent performance reports dashboard.
     */
    public function index(Request $request): View
    {
        $accountId = $request->user()->account_id;
        abort_unless($accountId, 403, 'User must belong to an account');

        $performanceService = new AgentPerformanceService($accountId);

        // Get all agents for selection
        $agents = User::where('account_id', $accountId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'team_role_id']);

        // Get all teams
        $teams = Team::where('account_id', $accountId)
            ->withCount('members')
            ->orderBy('name')
            ->get();

        // Default date range (last 7 days)
        $endDate = now();
        $startDate = now()->subDays(6);

        return view('Chat::chats.reports.agent-performance.index', compact('agents', 'teams', 'startDate', 'endDate'));
    }

    /**
     * Get performance metrics for a specific agent.
     */
    public function agentMetrics(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $accountId = $request->user()->account_id;
        User::where('account_id', $accountId)->findOrFail($userId);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $metrics = $performanceService->getAgentMetrics($userId, $start, $end);

        return response()->json($metrics);
    }

    /**
     * Get leaderboard for a specific metric.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric' => 'required|string|in:conversations_resolved,messages_sent,first_response_time,resolution_time,csat_rating',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();
        $limit = $validated['limit'] ?? 10;

        $leaderboard = $performanceService->getLeaderboard($validated['metric'], $start, $end, $limit);

        return response()->json([
            'metric' => $validated['metric'],
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Get team comparison metrics.
     */
    public function teamComparison(Request $request, int $teamId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $comparison = $performanceService->getTeamComparison($teamId, $start, $end);

        return response()->json($comparison);
    }

    /**
     * Get trend data for an agent and metric.
     */
    public function trends(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'metric' => 'required|string|in:conversations_resolved,messages_sent,first_response_time,csat_rating',
            'days' => 'nullable|integer|min:7|max:90',
        ]);

        $accountId = $request->user()->account_id;

        // Verify the agent belongs to the current account
        User::where('account_id', $accountId)->findOrFail($userId);

        $days = $validated['days'] ?? 30;
        $trendData = $performanceService->getTrendData($userId, $validated['metric'], $days);

        return response()->json([
            'metric' => $validated['metric'],
            'days' => $days,
            'data' => $trendData,
        ]);
    }

    /**
     * Compare multiple agents side-by-side.
     */
    public function compareAgents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_ids' => 'required|array|min:2|max:5',
            'agent_ids.*' => 'required|integer|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $comparison = collect($validated['agent_ids'])->map(function ($agentId) use ($start, $end) {
            return $performanceService->getAgentMetrics($agentId, $start, $end);
        });

        return response()->json([
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'agents' => $comparison,
        ]);
    }

    /**
     * Get current agent's own performance (for self-view).
     */
    public function myPerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->subDays(6)->startOfDay();

        $end = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        $metrics = $performanceService->getAgentMetrics($request->user()->id, $start, $end);

        return response()->json($metrics);
    }

    /**
     * Export agent performance report to PDF, CSV, or Excel.
     */
    public function export(Request $request, int $userId)
    {
        $accountId = $request->user()->account_id;
        abort_unless($accountId, 403, 'User must belong to an account');

        $performanceService = new AgentPerformanceService($accountId);
        $exportService = new AgentPerformanceExportService($accountId);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'nullable|string|in:pdf,csv,xlsx',
        ]);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $metrics = $performanceService->getAgentMetrics($userId, $start, $end);

        $format = $validated['format'] ?? 'pdf';
        $filename = 'agent-performance-'.$metrics['agent']['name'].'-'.$start->format('Y-m-d').'-to-'.$end->format('Y-m-d');

        return match ($format) {
            'pdf' => $exportService->exportPdf($metrics, $start, $end, $filename),
            'csv', 'xlsx' => $exportService->exportExcel($metrics, $start, $end, $filename, $format),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid export format',
            ], 400),
        };
    }

    /**
     * Get overview of all agents performance (summary).
     */
    public function overview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $accountId = $request->user()->account_id;
        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $agents = User::where('account_id', $accountId)->get();

        $agentSummaries = $agents->map(function ($agent) use ($start, $end) {
            $metrics = $performanceService->getAgentMetrics($agent->id, $start, $end);

            return [
                'agent' => $metrics['agent'],
                'conversations_resolved' => $metrics['conversation']['resolved'] ?? 0,
                'messages_sent' => $metrics['messages']['sent'] ?? 0,
                'first_response_avg' => $metrics['response_times']['first_response_avg'] ?? 0,
                'csat_rating' => $metrics['csat']['avg_rating'] ?? 0,
            ];
        });

        return response()->json([
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'total_agents' => $agents->count(),
            'agents' => $agentSummaries,
        ]);
    }
}
