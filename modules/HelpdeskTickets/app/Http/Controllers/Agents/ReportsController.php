<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Models\Ticket;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();

        $from = $request->date('from', now()->startOfMonth());
        $to = $request->date('to', now()->endOfMonth());

        $baseQuery = Ticket::query()
            ->where('assignee_id', $userId)
            ->whereBetween('created_at', [$from, $to]);

        $cacheKey = "helpdesk:agent:reports:{$userId}:{$from->format('Y-m-d')}:{$to->format('Y-m-d')}";

        $stats = Cache::remember($cacheKey, 60, function () use ($baseQuery) {
            $row = (clone $baseQuery)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(status_id IN (SELECT id FROM helpdesk_ticket_statuses WHERE is_open = 1)) as open')
                ->selectRaw('SUM(status_id IN (SELECT id FROM helpdesk_ticket_statuses WHERE is_open = 0)) as closed')
                ->selectRaw('SUM(resolved_at IS NOT NULL) as resolved')
                ->selectRaw('SUM(sla_first_response_breached = 1 OR sla_next_response_breached = 1 OR sla_resolution_breached = 1) as breached')
                ->first();

            return [
                'total' => (int) $row->total,
                'open' => (int) $row->open,
                'closed' => (int) $row->closed,
                'resolved' => (int) $row->resolved,
                'breached' => (int) $row->breached,
            ];
        });

        $byStatus = (clone $baseQuery)
            ->with('status:id,name,color')
            ->get(['id', 'status_id'])
            ->groupBy('status.name')
            ->map->count();

        $byPriority = (clone $baseQuery)
            ->get(['id', 'priority'])
            ->groupBy('priority')
            ->map->count();

        return view('helpdesktickets::agents.reports.index', compact('stats', 'byStatus', 'byPriority', 'from', 'to'));
    }

    public function export(Request $request): Response
    {
        $userId = auth()->id();

        $from = $request->date('from', now()->startOfMonth());
        $to = $request->date('to', now()->endOfMonth());

        $tickets = Ticket::query()
            ->with(['status:id,name', 'category:id,name', 'customer:id,name,email'])
            ->where('assignee_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->cursor();

        $csv = implode(',', ['Ticket #', 'Subject', 'Status', 'Category', 'Priority', 'Customer', 'Created', 'Resolved'])."\n";

        foreach ($tickets as $ticket) {
            $csv .= implode(',', [
                $ticket->ticket_number,
                '"'.str_replace('"', '""', $ticket->subject).'"',
                $ticket->status?->name ?? '',
                $ticket->category?->name ?? '',
                $ticket->priority ?? '',
                '"'.str_replace('"', '""', $ticket->customer?->name ?? '').'"',
                $ticket->created_at?->format('Y-m-d H:i'),
                $ticket->resolved_at?->format('Y-m-d H:i') ?? '',
            ])."\n";
        }

        $filename = "tickets-report-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
