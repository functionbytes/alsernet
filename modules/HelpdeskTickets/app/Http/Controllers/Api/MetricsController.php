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

        // Métricas de calidad del mes en curso (tickets resueltos/valorados por
        // agente): CSAT medio, cumplimiento de SLA de resolución y tiempo medio
        // de resolución. Se calculan sobre los cerrados/valorados de este mes
        // para que reflejen el rendimiento reciente, no todo el histórico.
        $quality = Ticket::query()
            ->selectRaw('assignee_id')
            ->selectRaw('AVG(CASE WHEN rated_at IS NOT NULL THEN rating END) as avg_rating')
            ->selectRaw('SUM(closed_at IS NOT NULL) as resolved_count')
            ->selectRaw('SUM(closed_at IS NOT NULL AND sla_resolution_breached = 0) as sla_met')
            ->selectRaw('AVG(CASE WHEN closed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, closed_at) END) as avg_resolution_minutes')
            ->whereNotNull('assignee_id')
            ->whereMonth('closed_at', now()->month)
            ->whereYear('closed_at', now()->year)
            ->groupBy('assignee_id')
            ->get()
            ->keyBy('assignee_id');

        $agentIds = $rows->pluck('assignee_id')->merge($quality->keys())->unique();
        // La tabla users no tiene columna `name` (es un accessor firstname+lastname
        // que SQL no resuelve); se compone desde las columnas reales.
        $users = User::whereIn('id', $agentIds)
            ->get(['id', 'firstname', 'lastname'])
            ->mapWithKeys(fn ($u) => [
                $u->id => trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: 'Unknown',
            ]);

        $openByAgent = $rows->pluck('open_tickets', 'assignee_id');

        $data = $agentIds->map(function ($agentId) use ($users, $openByAgent, $closedToday, $slaBreached, $quality) {
            $q = $quality[$agentId] ?? null;
            $resolved = (int) ($q->resolved_count ?? 0);

            return [
                'agentId' => $agentId,
                'name' => $users[$agentId] ?? 'Unknown',
                'openTickets' => (int) ($openByAgent[$agentId] ?? 0),
                'closedToday' => (int) ($closedToday[$agentId] ?? 0),
                'slaBreached' => (int) ($slaBreached[$agentId] ?? 0),
                'resolvedThisMonth' => $resolved,
                'avgRating' => $q && $q->avg_rating !== null ? round((float) $q->avg_rating, 2) : null,
                'slaComplianceRate' => $resolved > 0 ? round((int) $q->sla_met / $resolved * 100, 1) : null,
                'avgResolutionMinutes' => $q && $q->avg_resolution_minutes !== null ? (int) round((float) $q->avg_resolution_minutes) : null,
            ];
        });

        return ApiResponse::success($data->values()->all());
    }
}
