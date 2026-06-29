<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.view');
    }

    public function index(): View
    {
        $thirtyDaysAgo = now()->subDays(30);

        $data = Cache::remember('helpdesk:reports', 300, function () use ($thirtyDaysAgo) {
            $slaRow = Ticket::query()
                ->selectRaw('SUM(sla_policy_id IS NOT NULL) as sla_total')
                ->selectRaw('SUM(sla_first_response_breached = 1 OR sla_resolution_breached = 1) as sla_breached')
                ->first();

            return [
                'volume' => Ticket::where('created_at', '>=', $thirtyDaysAgo)
                    ->selectRaw('DATE(created_at) as day, COUNT(*) as created, SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved')
                    ->groupBy('day')
                    ->orderBy('day')
                    ->get(),

                'by_status' => TicketStatus::withCount('tickets')
                    ->get()
                    ->map(fn ($s) => [
                        'label' => $s->name,
                        'value' => $s->tickets_count,
                        'color' => $s->color ?? '#90bb13',
                    ]),

                'by_priority' => Ticket::selectRaw('priority, COUNT(*) as total')
                    ->groupBy('priority')
                    ->get()
                    ->pluck('total', 'priority'),

                'sla_total' => (int) $slaRow->sla_total,
                'sla_breached' => (int) $slaRow->sla_breached,

                'top_agents' => (function () {
                    $counts = Ticket::whereNotNull('resolved_at')
                        ->whereNotNull('assignee_id')
                        ->selectRaw('assignee_id, COUNT(*) as total')
                        ->groupBy('assignee_id')
                        ->orderByDesc('total')
                        ->take(5)
                        ->pluck('total', 'assignee_id')
                        ->toArray();

                    if (empty($counts)) {
                        return collect();
                    }

                    return User::whereIn('id', array_keys($counts))
                        ->select('id', 'firstname', 'lastname')
                        ->get()
                        ->map(function ($u) use ($counts) {
                            $u->resolved_count = $counts[$u->id] ?? 0;

                            return $u;
                        })
                        ->sortByDesc('resolved_count')
                        ->values();
                })(),

                'avg_resolution_minutes' => (int) Ticket::whereNotNull('resolved_at')
                    ->whereNotNull('created_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg')
                    ->value('avg'),

                'csat_distribution' => Ticket::whereNotNull('rating')
                    ->selectRaw('rating, COUNT(*) as total')
                    ->groupBy('rating')
                    ->pluck('total', 'rating'),
            ];
        });

        return view('helpdesktickets::managers.reports.index', ['data' => $data]);
    }
}
