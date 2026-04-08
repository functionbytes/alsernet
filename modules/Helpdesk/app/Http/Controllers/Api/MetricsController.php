<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Ticket;

class MetricsController extends Controller
{
    /**
     * Return a summary of key helpdesk metrics.
     *
     * GET /api/helpdesk/metrics/summary
     */
    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'open' => Ticket::whereNull('closed_at')->count(),
            'closed_today' => Ticket::whereDate('closed_at', today())->count(),
            'sla_breached' => Ticket::where('sla_resolution_breached', true)->whereNull('closed_at')->count(),
            'unassigned' => Ticket::whereNull('assignee_id')->whereNull('closed_at')->count(),
        ]);
    }

    /**
     * Return per-agent ticket metrics.
     *
     * GET /api/helpdesk/metrics/by-agent
     */
    public function byAgent(Request $request): JsonResponse
    {
        $rows = Ticket::query()
            ->selectRaw('assignee_id, COUNT(*) as open_tickets')
            ->whereNull('closed_at')
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->get();

        $closedToday = Ticket::query()
            ->selectRaw('assignee_id, COUNT(*) as closed_today')
            ->whereDate('closed_at', today())
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->pluck('closed_today', 'assignee_id');

        $slaBreached = Ticket::query()
            ->selectRaw('assignee_id, COUNT(*) as sla_breached')
            ->where('sla_resolution_breached', true)
            ->whereNull('closed_at')
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->pluck('sla_breached', 'assignee_id');

        $agentIds = $rows->pluck('assignee_id')->unique()->all();
        $users = User::whereIn('id', $agentIds)->pluck('name', 'id');

        $data = $rows->map(fn ($row) => [
            'agent_id' => $row->assignee_id,
            'name' => $users[$row->assignee_id] ?? 'Unknown',
            'open_tickets' => (int) $row->open_tickets,
            'closed_today' => (int) ($closedToday[$row->assignee_id] ?? 0),
            'sla_breached' => (int) ($slaBreached[$row->assignee_id] ?? 0),
        ]);

        return response()->json($data);
    }
}
