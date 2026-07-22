<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\CsatRating;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\OpsHealthService;
use Modules\HelpdeskTickets\Support\ReportsCache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helpdesk reports dashboard at /panel/helpdesk/reports — owned by HelpdeskTickets
 * since the data is 100% ticket-based.
 */
class HelpdeskReportsController extends Controller
{
    /**
     * Show the reports dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('helpdesk.metrics.view');

        [$from, $to] = $this->resolveDateRange($request);

        $cacheKey = ReportsCache::key('index:'.$from->format('Y-m-d').':'.$to->format('Y-m-d'));

        $viewData = Cache::remember($cacheKey, 300, function () use ($from, $to) {
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
        });

        return view('helpdesk::helpdesk.reports.index', array_merge($viewData, [
            'from' => $from,
            'to' => $to,
            // Salud operativa "ahora" (colas, dead-letters, webhooks, SLA, IA):
            // fuera del cache por rango de fechas — la refresca el comando
            // programado helpdesk:ops-metrics y aquí solo se lee.
            'opsHealth' => app(OpsHealthService::class)->cached(),
        ]));
    }

    /**
     * Export ticket stats as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('helpdesk.metrics.export');

        [$from, $to] = $this->resolveDateRange($request);

        $filename = 'tickets-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Ticket#', 'Asunto', 'Cliente', 'Email', 'Estado', 'Categoria',
                'Prioridad', 'Agente', 'Creado', 'Cerrado',
                'Tiempo respuesta (min)', 'Tiempo resolucion (min)', 'SLA Incumplido',
            ]);

            Ticket::with(['customer:id,name,email', 'status:id,name', 'category:id,name', 'assignee:id,firstname,lastname'])
                ->whereBetween('created_at', [$from, $to])
                ->cursor()
                ->each(function (Ticket $ticket) use ($handle) {
                    $responseTime = $ticket->first_response_at
                        ? $ticket->created_at->diffInMinutes($ticket->first_response_at)
                        : '';

                    $resolutionTime = $ticket->closed_at
                        ? $ticket->created_at->diffInMinutes($ticket->closed_at)
                        : '';

                    fputcsv($handle, [
                        $ticket->ticket_number,
                        $ticket->subject,
                        $ticket->customer->name ?? '',
                        $ticket->customer->email ?? '',
                        $ticket->status->name ?? '',
                        $ticket->category->name ?? '',
                        $ticket->priority,
                        $ticket->assignee->name ?? '',
                        $ticket->created_at->format('Y-m-d H:i'),
                        $ticket->closed_at?->format('Y-m-d H:i') ?? '',
                        $responseTime,
                        $resolutionTime,
                        $ticket->sla_resolution_breached ? 'Si' : 'No',
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Resolve from/to dates from request, defaulting to last 30 days.
     * Malformed or inverted input falls back to the default range instead of
     * bubbling a Carbon parse exception (500).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $from = $this->parseDateInput($request->input('from')) ?? now()->subDays(30);
        $to = $this->parseDateInput($request->input('to')) ?? now();

        if ($from->greaterThan($to)) {
            $from = now()->subDays(30);
            $to = now();
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * Parse a request date value, returning null when missing or invalid.
     */
    private function parseDateInput(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
