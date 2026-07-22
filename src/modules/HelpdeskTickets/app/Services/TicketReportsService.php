<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\CsatRating;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Queries del dashboard de reports de tickets, extraídas de
 * HelpdeskReportsController::index para poder reutilizarlas desde el informe
 * programado por email (helpdesk:send-scheduled-reports) sin duplicar SQL.
 * El caching (ReportsCache + Cache::remember) sigue siendo responsabilidad
 * del caller: el controller cachea por rango, el comando consulta en fresco.
 */
class TicketReportsService
{
    /**
     * Resumen del periodo: totales de tickets, distribución por estado /
     * categoría / prioridad, top agentes, valoraciones y CSAT.
     *
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $statsRow = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_created,
                SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) as total_closed,
                SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as total_resolved,
                SUM(CASE WHEN sla_resolution_breached = 1 THEN 1 ELSE 0 END) as sla_breached,
                AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_response_time,
                AVG(CASE WHEN closed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, closed_at) END) as avg_resolution_time
            ')
            ->first();

        $byStatus = Ticket::with('status:id,name,color')
            ->whereBetween('created_at', [$from, $to])
            ->select('status_id', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('status_id')
            ->get();

        $byCategory = Ticket::with('category:id,name,color,icon')
            ->whereBetween('created_at', [$from, $to])
            ->select('category_id', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->get();

        $byPriority = Ticket::whereBetween('created_at', [$from, $to])
            ->select('priority', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();

        $topAgentRows = Ticket::query()
            ->select('assignee_id', DB::connection('helpdesk')->raw('COUNT(*) as closed_count'))
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->orderByDesc('closed_count')
            ->limit(5)
            ->get();

        $topAgentUsers = User::whereIn('id', $topAgentRows->pluck('assignee_id'))
            ->select(['id', 'firstname', 'lastname'])
            ->get()
            ->keyBy('id');

        $topAgents = $topAgentRows
            ->map(fn ($row) => [
                'agent' => $topAgentUsers->get($row->assignee_id),
                'closed_count' => $row->closed_count,
            ])
            ->filter(fn ($r) => $r['agent'] !== null)
            ->values();

        $ratedRow = Ticket::whereBetween('created_at', [$from, $to])
            ->whereNotNull('rated_at')
            ->selectRaw('COUNT(*) as rated_count, AVG(rating) as avg_rating')
            ->first();

        $ratingDistribution = Ticket::whereBetween('created_at', [$from, $to])
            ->whereNotNull('rated_at')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        $csatRow = CsatRating::query()
            ->whereBetween('answered_at', [$from, $to])
            ->whereNotNull('answered_at')
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_count')
            ->first();

        $csatDistribution = CsatRating::query()
            ->whereBetween('answered_at', [$from, $to])
            ->whereNotNull('answered_at')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        return [
            'totalCreated' => (int) $statsRow->total_created,
            'totalClosed' => (int) $statsRow->total_closed,
            'totalResolved' => (int) $statsRow->total_resolved,
            'slaBreached' => (int) $statsRow->sla_breached,
            'avgResponseTime' => round($statsRow->avg_response_time ?? 0),
            'avgResolutionTime' => round($statsRow->avg_resolution_time ?? 0),
            'byStatus' => $byStatus,
            'byCategory' => $byCategory,
            'byPriority' => $byPriority,
            'topAgents' => $topAgents,
            'avgRating' => round($ratedRow->avg_rating ?? 0, 1),
            'ratedCount' => (int) $ratedRow->rated_count,
            'ratingDistribution' => $ratingDistribution,
            'csatAvg' => round($csatRow->avg_rating ?? 0, 1),
            'csatTotal' => (int) $csatRow->total,
            'csatPositive' => (int) $csatRow->positive_count,
            'csatDistribution' => $csatDistribution,
        ];
    }
}
