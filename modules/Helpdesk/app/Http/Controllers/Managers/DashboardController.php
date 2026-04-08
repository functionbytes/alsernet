<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Ticket;

class DashboardController extends Controller
{
    public function index(): View
    {
        $ticketStats = [
            'open' => Ticket::whereNull('closed_at')->count(),
            'closed_today' => Ticket::whereDate('closed_at', today())->count(),
            'created_today' => Ticket::whereDate('created_at', today())->count(),
            'sla_breached' => Ticket::where('sla_resolution_breached', true)->whereNull('closed_at')->count(),
            'unassigned' => Ticket::whereNull('assignee_id')->whereNull('closed_at')->count(),
            'overdue' => Ticket::whereNotNull('sla_resolution_due_at')
                ->where('sla_resolution_due_at', '<', now())
                ->whereNull('closed_at')->count(),
        ];

        $convStats = [
            'open' => Conversation::whereNull('closed_at')->count(),
            'unassigned' => Conversation::whereNull('assignee_id')->whereNull('closed_at')->count(),
        ];

        $agentStats = User::select(['users.id', 'users.name'])
            ->selectRaw('COUNT(t.id) as open_tickets')
            ->selectRaw('SUM(CASE WHEN DATE(t.closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today')
            ->join('helpdesk_tickets as t', 't.assignee_id', '=', 'users.id')
            ->whereNull('t.closed_at')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('open_tickets')
            ->limit(5)
            ->get();

        $recentBreaches = Ticket::where('sla_resolution_breached', true)
            ->whereNull('closed_at')
            ->with(['customer:id,name', 'assignee:id,name', 'status:id,name,color'])
            ->orderBy('sla_resolution_due_at')
            ->limit(5)
            ->get();

        $recentTickets = Ticket::with(['customer:id,name', 'status:id,name,color', 'assignee:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $avgRating = round(
            Ticket::whereNotNull('rated_at')
                ->whereMonth('rated_at', now()->month)
                ->avg('rating') ?? 0,
            1
        );

        return view('helpdesk::managers.helpdesk.dashboard', compact(
            'ticketStats', 'convStats', 'agentStats', 'recentBreaches', 'recentTickets', 'avgRating'
        ));
    }
}
