<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Models\Ticket;

class MetricsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.metrics.view');

        $data = Cache::remember('helpdesk:api:metrics', 60, function () {
            $row = Ticket::query()
                ->selectRaw('SUM(closed_at IS NULL) as open')
                ->selectRaw('SUM(DATE(closed_at) = CURDATE()) as closedToday')
                ->selectRaw('SUM(sla_resolution_breached = 1 AND closed_at IS NULL) as slaBreached')
                ->selectRaw('SUM(assignee_id IS NULL AND closed_at IS NULL) as unassigned')
                ->first();

            return [
                'open' => (int) $row->open,
                'closedToday' => (int) $row->closedToday,
                'slaBreached' => (int) $row->slaBreached,
                'unassigned' => (int) $row->unassigned,
            ];
        });

        return ApiResponse::success($data);
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
