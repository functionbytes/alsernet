<?php

namespace Modules\Helpdesk\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Ticket;

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

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->open()->count(),
            'closed' => (clone $baseQuery)->closed()->count(),
            'resolved' => (clone $baseQuery)->resolved()->count(),
            'breached' => (clone $baseQuery)->slaBreach()->count(),
        ];

        $byStatus = (clone $baseQuery)
            ->with('status:id,name,color')
            ->get(['id', 'status_id'])
            ->groupBy('status.name')
            ->map->count();

        $byPriority = (clone $baseQuery)
            ->get(['id', 'priority'])
            ->groupBy('priority')
            ->map->count();

        return view('helpdesk::agents.reports.index', compact('stats', 'byStatus', 'byPriority', 'from', 'to'));
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
            ->get();

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
