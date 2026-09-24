<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\CsatRating;
use Modules\HelpdeskSla\Services\BusinessHoursCalculator;
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
            // Deflexión del portal: sugerencias mostradas, artículos abiertos
            // y tickets abiertos igualmente tras ver sugerencias.
            ...$this->deflectionMetrics($from, $to),
            // Mediana y calidad de resolución (24-sep-2026). La media se
            // dispara con un solo ticket olvidado un mes; la mediana dice
            // cuánto tarda el caso típico.
            ...$this->qualityMetrics($from, $to),
            'byStatus' => $byStatus,
            'byCategory' => $byCategory,
            'byPriority' => $byPriority,
            'byChannel' => $byChannel,
            'trend' => $this->dailyTrend($from, $to),
            'topAgents' => $topAgents,
            'backlogAging' => $this->backlogAging(),
            'agentMetrics' => $this->agentMetrics($from, $to),
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
     * Antigüedad del backlog AHORA (no depende del rango): tickets sin
     * resolver ni cerrar por tramos de edad, para ver si la cola envejece
     * aunque el volumen diario parezca bajo (24-sep-2026).
     *
     * @return array{buckets: array<string, array{label: string, count: int}>, total: int, unassigned: int, oldest_days: ?int}
     */
    public function backlogAging(): array
    {
        $now = now();
        $open = Ticket::query()->whereNull('closed_at')->whereNull('resolved_at');

        $counts = (clone $open)
            ->selectRaw(
                "CASE WHEN created_at >= ? THEN 'lt1' WHEN created_at >= ? THEN 'd1_3' WHEN created_at >= ? THEN 'd3_7' WHEN created_at >= ? THEN 'd7_30' ELSE 'gt30' END as bucket, COUNT(*) as c",
                [$now->copy()->subDay(), $now->copy()->subDays(3), $now->copy()->subDays(7), $now->copy()->subDays(30)]
            )
            ->groupBy('bucket')
            ->pluck('c', 'bucket');

        $labels = ['lt1' => 'Menos de 1 día', 'd1_3' => '1–3 días', 'd3_7' => '3–7 días', 'd7_30' => '7–30 días', 'gt30' => 'Más de 30 días'];
        $buckets = [];
        foreach ($labels as $key => $label) {
            $buckets[$key] = ['label' => $label, 'count' => (int) ($counts[$key] ?? 0)];
        }

        $oldest = (clone $open)->min('created_at');

        return [
            'buckets' => $buckets,
            'total' => array_sum(array_column($buckets, 'count')),
            'unassigned' => (clone $open)->whereNull('assignee_id')->count(),
            'oldest_days' => $oldest ? (int) Carbon::parse($oldest)->diffInDays($now) : null,
        ];
    }

    /**
     * Métricas por agente: todos los que tuvieron actividad en el periodo o
     * tienen tickets abiertos, no solo el top 5 de cerrados.
     *
     * @return Collection<int, array{agent_id: int, name: string, solved: int, open_now: int, breached_now: int, avg_first_response_minutes: ?int, avg_resolution_minutes: ?int, avg_rating: ?float}>
     */
    public function agentMetrics(Carbon $from, Carbon $to): Collection
    {
        $solved = Ticket::query()
            ->whereNotNull('assignee_id')
            ->whereRaw('COALESCE(resolved_at, closed_at) BETWEEN ? AND ?', [$from, $to])
            ->selectRaw('assignee_id, COUNT(*) as solved, AVG(GREATEST(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(resolved_at, closed_at)), 0)) as avg_resolution')
            ->groupBy('assignee_id')
            ->get()
            ->keyBy('assignee_id');

        $openNow = Ticket::query()
            ->whereNotNull('assignee_id')
            ->whereNull('closed_at')
            ->whereNull('resolved_at')
            ->selectRaw('assignee_id, COUNT(*) as open_now, SUM(CASE WHEN sla_resolution_breached = 1 THEN 1 ELSE 0 END) as breached_now')
            ->groupBy('assignee_id')
            ->get()
            ->keyBy('assignee_id');

        $created = Ticket::query()
            ->whereNotNull('assignee_id')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('assignee_id, AVG(CASE WHEN first_response_at IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(MINUTE, created_at, first_response_at), 0) END) as avg_first_response, AVG(CASE WHEN rated_at IS NOT NULL THEN rating END) as avg_rating')
            ->groupBy('assignee_id')
            ->get()
            ->keyBy('assignee_id');

        $ids = $solved->keys()->merge($openNow->keys())->merge($created->keys())->unique()->values();
        $users = User::whereIn('id', $ids)->get(['id', 'firstname', 'lastname'])->keyBy('id');

        return $ids
            ->map(function ($id) use ($solved, $openNow, $created, $users) {
                $user = $users->get($id);
                if (! $user) {
                    return null;
                }
                $avgFirst = $created->get($id)?->avg_first_response;
                $avgRes = $solved->get($id)?->avg_resolution;
                $rating = $created->get($id)?->avg_rating;

                return [
                    'agent_id' => (int) $id,
                    'name' => $user->fullName(),
                    'solved' => (int) ($solved->get($id)?->solved ?? 0),
                    'open_now' => (int) ($openNow->get($id)?->open_now ?? 0),
                    'breached_now' => (int) ($openNow->get($id)?->breached_now ?? 0),
                    'avg_first_response_minutes' => $avgFirst !== null ? (int) round($avgFirst) : null,
                    'avg_resolution_minutes' => $avgRes !== null ? (int) round($avgRes) : null,
                    'avg_rating' => $rating !== null ? round((float) $rating, 1) : null,
                ];
            })
            ->filter()
            ->sortByDesc('solved')
            ->values();
    }

    /**
     * Mediana de primera respuesta y de resolución, tasa de reapertura y
     * resolución al primer contacto (un solo mensaje público de agente),
     * sobre los tickets creados en el periodo.
     *
     * @return array{medianResponseTime: int, medianResolutionTime: int, medianBusinessResponseTime: ?int, medianBusinessResolutionTime: ?int, reopenRate: float, firstContactResolutionRate: float}
     */
    private function qualityMetrics(Carbon $from, Carbon $to): array
    {
        $base = fn () => Ticket::query()->whereBetween('created_at', [$from, $to]);

        $responseMinutes = $base()->whereNotNull('first_response_at')->limit(50000)->get(['created_at', 'first_response_at'])
            ->map(fn (Ticket $t) => max(0, (int) $t->created_at->diffInMinutes($t->first_response_at)));
        $resolutionMinutes = $base()->whereNotNull('closed_at')->limit(50000)->get(['created_at', 'closed_at'])
            ->map(fn (Ticket $t) => max(0, (int) $t->created_at->diffInMinutes($t->closed_at)));

        $finished = $base()->where(fn ($q) => $q->whereNotNull('resolved_at')->orWhereNotNull('closed_at'));
        $finishedCount = (clone $finished)->count();

        $reopened = $finishedCount === 0 ? 0 : (clone $finished)
            ->whereHas('items', fn ($q) => $q->where('type', 'reopened'))
            ->count();

        $firstContact = $finishedCount === 0 ? 0 : (clone $finished)
            ->whereDoesntHave('items', fn ($q) => $q->where('type', 'reopened'))
            ->whereHas('items', fn ($q) => $q->where('type', 'message')->where('is_internal', false)->whereNotNull('user_id'), '=', 1)
            ->count();

        // Mismas medianas contando solo horario laboral (calendario y
        // festivos de HelpdeskSla). Un ticket del viernes a las 18:00
        // contestado el lunes a las 9:00 no son 63 horas de espera real.
        $businessResponse = $this->businessMedian($base()->whereNotNull('first_response_at')->limit(5000)->get(['created_at', 'first_response_at']), 'first_response_at');
        $businessResolution = $this->businessMedian($base()->whereNotNull('closed_at')->limit(5000)->get(['created_at', 'closed_at']), 'closed_at');

        return [
            'medianResponseTime' => (int) round($responseMinutes->median() ?? 0),
            'medianResolutionTime' => (int) round($resolutionMinutes->median() ?? 0),
            'medianBusinessResponseTime' => $businessResponse,
            'medianBusinessResolutionTime' => $businessResolution,
            'reopenRate' => $finishedCount > 0 ? round($reopened / $finishedCount * 100, 1) : 0.0,
            'firstContactResolutionRate' => $finishedCount > 0 ? round($firstContact / $finishedCount * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array{deflectionShown: int, deflectionClicked: int, deflectionCreated: int, deflectionRate: ?float}
     */
    private function deflectionMetrics(Carbon $from, Carbon $to): array
    {
        try {
            $counts = DB::connection('helpdesk')
                ->table('helpdesk_ticket_deflection_events')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('event, COUNT(*) as c')
                ->groupBy('event')
                ->pluck('c', 'event');
        } catch (\Throwable) {
            $counts = collect();
        }

        $shown = (int) ($counts['shown'] ?? 0);
        $created = (int) ($counts['created'] ?? 0);

        return [
            'deflectionShown' => $shown,
            'deflectionClicked' => (int) ($counts['clicked'] ?? 0),
            'deflectionCreated' => $created,
            // Quien vio sugerencias y NO acabó abriendo ticket.
            'deflectionRate' => $shown > 0 ? round(max(0, $shown - $created) / $shown * 100, 1) : null,
        ];
    }

    /**
     * Mediana en minutos laborables, o null si HelpdeskSla no está instalado.
     */
    private function businessMedian(Collection $tickets, string $endField): ?int
    {
        if (! class_exists(BusinessHoursCalculator::class)) {
            return null;
        }

        try {
            $calculator = app(BusinessHoursCalculator::class);

            $minutes = $tickets->map(fn (Ticket $t) => $calculator->businessMinutesBetween($t->created_at, $t->{$endField}));

            return (int) round($minutes->median() ?? 0);
        } catch (\Throwable) {
            return null;
        }
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
