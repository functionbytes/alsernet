<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Http\Requests\DateRangeRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketDailyReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    /**
     * Show the reports dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('helpdesk.metrics.view');

        [$from, $to] = $this->resolveDateRange($request);

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
            ->select(
                'assignee_id',
                DB::connection('helpdesk')->raw('COUNT(*) as closed_count')
            )
            ->whereBetween('closed_at', [$from, $to])
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->orderByDesc('closed_count')
            ->limit(5)
            ->get();

        $topAgentUsers = User::whereIn('id', $topAgentRows->pluck('assignee_id'))
            ->select(['id', 'name'])
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
            ->selectRaw('
                COUNT(*) as rated_count,
                AVG(rating) as avg_rating
            ')
            ->first();

        $ratingDistribution = Ticket::whereBetween('created_at', [$from, $to])
            ->whereNotNull('rated_at')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating');

        return view('helpdesk::managers.helpdesk.reports.index', [
            'from' => $from,
            'to' => $to,
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
        ]);
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

            Ticket::with(['customer:id,name,email', 'status:id,name', 'category:id,name', 'assignee:id,name'])
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
     * Return agent performance data as JSON.
     */
    public function agentPerformance(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.metrics.view');

        [$from, $to] = $this->resolveDateRange($request);

        $agentRows = Ticket::query()
            ->select(
                'assignee_id',
                DB::connection('helpdesk')->raw('COUNT(*) as assigned_count'),
                DB::connection('helpdesk')->raw('SUM(CASE WHEN closed_at IS NOT NULL THEN 1 ELSE 0 END) as closed_count'),
                DB::connection('helpdesk')->raw('AVG(CASE WHEN first_response_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at) ELSE NULL END) as avg_response_time'),
                DB::connection('helpdesk')->raw('AVG(CASE WHEN closed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, closed_at) ELSE NULL END) as avg_resolution_time'),
                DB::connection('helpdesk')->raw('SUM(CASE WHEN sla_resolution_breached = 1 THEN 1 ELSE 0 END) as sla_breaches')
            )
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('assignee_id')
            ->groupBy('assignee_id')
            ->get();

        $agentUsers = User::whereIn('id', $agentRows->pluck('assignee_id'))
            ->select(['id', 'name'])
            ->get()
            ->keyBy('id');

        $rows = $agentRows->map(function ($row) use ($agentUsers) {
            $agent = $agentUsers->get($row->assignee_id);

            return [
                'agent' => $agent?->name ?? 'Unknown',
                'assigned' => $row->assigned_count,
                'closed' => $row->closed_count,
                'avg_response_time' => round($row->avg_response_time ?? 0),
                'avg_resolution_time' => round($row->avg_resolution_time ?? 0),
                'sla_breaches' => $row->sla_breaches,
            ];
        });

        return response()->json($rows);
    }

    /**
     * Return daily trend data as JSON.
     */
    public function trend(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.metrics.view');

        [$from, $to] = $this->resolveDateRange($request);

        $reports = TicketDailyReport::whereBetween('report_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('report_date')
            ->get(['report_date', 'total_created', 'total_closed', 'total_resolved', 'sla_breached_count']);

        if ($reports->isNotEmpty()) {
            return response()->json($reports);
        }

        // Fallback: raw query grouped by date
        $created = Ticket::query()
            ->select(DB::connection('helpdesk')->raw('DATE(created_at) as report_date'), DB::connection('helpdesk')->raw('COUNT(*) as total_created'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::connection('helpdesk')->raw('DATE(created_at)'))
            ->pluck('total_created', 'report_date');

        $closed = Ticket::query()
            ->select(DB::connection('helpdesk')->raw('DATE(closed_at) as report_date'), DB::connection('helpdesk')->raw('COUNT(*) as total_closed'))
            ->whereBetween('closed_at', [$from, $to])
            ->groupBy(DB::connection('helpdesk')->raw('DATE(closed_at)'))
            ->pluck('total_closed', 'report_date');

        $dates = collect($created->keys()->merge($closed->keys())->unique()->sort());

        $data = $dates->map(fn ($date) => [
            'report_date' => $date,
            'total_created' => $created[$date] ?? 0,
            'total_closed' => $closed[$date] ?? 0,
            'total_resolved' => 0,
            'sla_breached_count' => 0,
        ])->values();

        return response()->json($data);
    }

    /**
     * Resolve from/to dates from request, defaulting to last 30 days.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(DateRangeRequest $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}
