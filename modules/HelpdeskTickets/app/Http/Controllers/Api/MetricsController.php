<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Models\Ticket;

class MetricsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.metrics.view');

        return ApiResponse::success([
            'open' => Ticket::whereNull('closed_at')->count(),
            'closedToday' => Ticket::whereDate('closed_at', today())->count(),
            'slaBreached' => Ticket::where('sla_resolution_breached', true)->whereNull('closed_at')->count(),
            'unassigned' => Ticket::whereNull('assignee_id')->whereNull('closed_at')->count(),
        ]);
    }

    public function byAgent(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.metrics.view');

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

        $users = User::whereIn('id', $rows->pluck('assignee_id'))->pluck('name', 'id');

        $data = $rows->map(fn ($row) => [
            'agentId' => $row->assignee_id,
            'name' => $users[$row->assignee_id] ?? 'Unknown',
            'openTickets' => (int) $row->open_tickets,
            'closedToday' => (int) ($closedToday[$row->assignee_id] ?? 0),
            'slaBreached' => (int) ($slaBreached[$row->assignee_id] ?? 0),
        ]);

        return ApiResponse::success($data->values()->all());
    }
}
