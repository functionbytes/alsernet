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
     * Umbral de días para decidir el bucketing del gráfico de tendencia
     * (dailyTrend): por encima de esto, agrupar por semana en vez de por día
     * para no saturar el eje X con rangos largos (p.ej. "Este año").
     */
    private const TREND_WEEKLY_THRESHOLD_DAYS = 45;

    /**
     * Umbral de días a partir del cual el bucketing pasa de semanal a
     * mensual (ver dailyTrend).
     */
    private const TREND_MONTHLY_THRESHOLD_DAYS = 180;

    /**
     * Resumen del periodo: totales de tickets, distribución por estado /
     * categoría / prioridad / canal, tendencia diaria, top agentes,
     * valoraciones, CSAT y comparación vs. el período inmediatamente
     * anterior de igual longitud.
     *
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $current = $this->totals($from, $to);

        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);
        $previous = $this->totals($prevFrom, $prevTo);

        $byStatus = Ticket::with('status:id,name,color')
            ->whereBetween('created_at', [$from, $to])
            ->select('status_id', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('status_id')
            ->get();

        $byCategory = Ticket::with('category:id,name,color,icon')
            ->whereBetween('created_at', [$from, $to])
            ->select('category_id', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->get();

        $byPriority = Ticket::whereBetween('created_at', [$from, $to])
            ->select('priority', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get();

        $byChannel = Ticket::whereBetween('created_at', [$from, $to])
            ->select('source', DB::connection('helpdesk')->raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
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

        $currentSlaRate = $this->slaComplianceRate((int) $current->total_created, (int) $current->sla_breached);
        $previousSlaRate = $this->slaComplianceRate((int) $previous->total_created, (int) $previous->sla_breached);

        return [
            'totalCreated' => (int) $current->total_created,
            'totalClosed' => (int) $current->total_closed,
            'totalResolved' => (int) $current->total_resolved,
            'slaBreached' => (int) $current->sla_breached,
            'slaComplianceRate' => $currentSlaRate,
            'avgResponseTime' => round($current->avg_response_time ?? 0),
            'avgResolutionTime' => round($current->avg_resolution_time ?? 0),
            'byStatus' => $byStatus,
            'byCategory' => $byCategory,
            'byPriority' => $byPriority,
            'byChannel' => $byChannel,
            'trend' => $this->dailyTrend($from, $to),
            'topAgents' => $topAgents,
            'avgRating' => round($ratedRow->avg_rating ?? 0, 1),
            'ratedCount' => (int) $ratedRow->rated_count,
            'ratingDistribution' => $ratingDistribution,
            'csatAvg' => round($csatRow->avg_rating ?? 0, 1),
            'csatTotal' => (int) $csatRow->total,
            'csatPositive' => (int) $csatRow->positive_count,
            'csatDistribution' => $csatDistribution,
            'previous' => [
                'totalCreated' => (int) $previous->total_created,
                'totalClosed' => (int) $previous->total_closed,
                'totalResolved' => (int) $previous->total_resolved,
                'slaComplianceRate' => $previousSlaRate,
            ],
            'changePercent' => [
                'totalCreated' => $this->pctChange((int) $current->total_created, (int) $previous->total_created),
                'totalClosed' => $this->pctChange((int) $current->total_closed, (int) $previous->total_closed),
                'totalResolved' => $this->pctChange((int) $current->total_resolved, (int) $previous->total_resolved),
                'slaComplianceRate' => $this->pctChange($currentSlaRate, $previousSlaRate),
            ],
        ];
    }

    /**
     * Totales agregados del periodo (conteos + tiempos promedio), usado
     * tanto para el rango actual como para el período anterior de
     * comparación — una sola query reutilizada en vez de duplicar el SQL.
     */
    private function totals(Carbon $from, Carbon $to): object
    {
        return Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_created,
                SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) as total_closed,
                SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as total_resolved,
                SUM(CASE WHEN sla_resolution_breached = 1 THEN 1 ELSE 0 END) as sla_breached,
                AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) END) as avg_response_time,
                AVG(CASE WHEN closed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, closed_at) END) as avg_resolution_time
            ')
            ->first();
    }

    /**
     * Rango inmediatamente anterior, de la misma duración en días que
     * [$from, $to], usado para calcular la variación % de las stat cards
     * (mismo espíritu que FormsReportController::buildTrend()).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function previousPeriod(Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [$prevFrom, $prevTo];
    }

    /**
     * Variación porcentual de $current vs $previous. Si no había datos en
     * el período anterior, se reporta +100% cuando ahora sí los hay (no
     * división por cero), o 0% si ambos son cero.
     */
    private function pctChange(int|float $current, int|float $previous): float
    {
        return match (true) {
            $previous > 0 => round((($current - $previous) / $previous) * 100, 1),
            $current > 0 => 100.0,
            default => 0.0,
        };
    }

    /**
     * % de tickets creados en el período que NO incumplieron el SLA de
     * resolución. Sin tickets creados, se reporta 100% (nada que incumplir)
     * en vez de dividir por cero.
     */
    private function slaComplianceRate(int $totalCreated, int $slaBreached): float
    {
        if ($totalCreated === 0) {
            return 100.0;
        }

        return round((1 - ($slaBreached / $totalCreated)) * 100, 1);
    }

    /**
     * Serie diaria de tickets creados en [$from, $to] para el gráfico de
     * tendencia. Bucketing adaptativo para no saturar el eje X en rangos
     * largos: por día hasta TREND_WEEKLY_THRESHOLD_DAYS, por semana hasta
     * TREND_MONTHLY_THRESHOLD_DAYS, por mes en rangos mayores (p.ej. "Este
     * año").
     *
     * @return array{labels: array<int, string>, series: array<int, int>}
     */
    private function dailyTrend(Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;

        return match (true) {
            $days <= self::TREND_WEEKLY_THRESHOLD_DAYS => $this->dailyTrendByDay($from, $to),
            $days <= self::TREND_MONTHLY_THRESHOLD_DAYS => $this->dailyTrendByWeek($from, $to),
            default => $this->dailyTrendByMonth($from, $to),
        };
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, int>}
     */
    private function dailyTrendByDay(Carbon $from, Carbon $to): array
    {
        $counts = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $series = [];

        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('d/m');
            $series[] = (int) ($counts->get($cursor->format('Y-m-d')) ?? 0);
            $cursor->addDay();
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, int>}
     */
    private function dailyTrendByWeek(Carbon $from, Carbon $to): array
    {
        $counts = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('YEARWEEK(created_at, 3) as bucket, MIN(DATE(created_at)) as week_start, COUNT(*) as total')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $counts->map(fn ($row) => 'Sem. '.Carbon::parse($row->week_start)->format('d/m'))->all(),
            'series' => $counts->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, series: array<int, int>}
     */
    private function dailyTrendByMonth(Carbon $from, Carbon $to): array
    {
        $counts = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $counts->map(fn ($row) => Carbon::createFromFormat('Y-m', $row->bucket)->translatedFormat('M Y'))->all(),
            'series' => $counts->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }
}
