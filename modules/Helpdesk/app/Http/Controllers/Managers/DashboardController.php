<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskTickets\Models\Ticket;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('access_helpdesk');

        $ticketStats = Cache::remember('helpdesk:dashboard:ticket_stats', 300, function () {
            $row = Ticket::query()
                ->selectRaw('
                    SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN DATE(closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as created_today,
                    SUM(CASE WHEN sla_resolution_breached = 1 AND closed_at IS NULL THEN 1 ELSE 0 END) as sla_breached,
                    SUM(CASE WHEN assignee_id IS NULL AND closed_at IS NULL THEN 1 ELSE 0 END) as unassigned,
                    SUM(CASE WHEN sla_resolution_due_at IS NOT NULL AND sla_resolution_due_at < NOW() AND closed_at IS NULL THEN 1 ELSE 0 END) as overdue
                ')
                ->first();

            return [
                'open' => (int) $row->open,
                'closed_today' => (int) $row->closed_today,
                'created_today' => (int) $row->created_today,
                'sla_breached' => (int) $row->sla_breached,
                'unassigned' => (int) $row->unassigned,
                'overdue' => (int) $row->overdue,
            ];
        });

        $convStats = Cache::remember('helpdesk:dashboard:conv_stats', 300, function () {
            $row = Conversation::query()
                ->selectRaw('
                    SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN assignee_id IS NULL AND closed_at IS NULL THEN 1 ELSE 0 END) as unassigned
                ')
                ->first();

            return [
                'open' => (int) $row->open,
                'unassigned' => (int) $row->unassigned,
            ];
        });

        $agentStats = Cache::remember('helpdesk:dashboard:agent_stats', 300, function () {
            return User::select(['users.id', 'users.firstname', 'users.lastname'])
                ->selectRaw('COUNT(t.id) as open_tickets')
                ->selectRaw('SUM(CASE WHEN DATE(t.closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today')
                ->join('helpdesk_tickets as t', 't.assignee_id', '=', 'users.id')
                ->whereNull('t.closed_at')
                ->groupBy('users.id', 'users.firstname', 'users.lastname')
                ->orderByDesc('open_tickets')
                ->limit(5)
                ->get();
        });

        $recentBreaches = Cache::remember('helpdesk:dashboard:recent_breaches', 60, function () {
            return Ticket::query()
                ->where('sla_resolution_breached', true)
                ->whereNull('closed_at')
                ->with(['customer:id,name', 'assignee:id,name', 'status:id,name,color'])
                ->orderBy('sla_resolution_due_at')
                ->limit(5)
                ->get();
        });

        $recentTickets = Cache::remember('helpdesk:dashboard:recent_tickets', 60, function () {
            return Ticket::query()
                ->with(['customer:id,name', 'status:id,name,color', 'assignee:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        });

        $avgRating = round(
            Ticket::query()
                ->whereNotNull('rated_at')
                ->whereMonth('rated_at', now()->month)
                ->avg('rating') ?? 0,
            1
        );

        return view('helpdesk::managers.dashboard', compact(
            'ticketStats', 'convStats', 'agentStats', 'recentBreaches', 'recentTickets', 'avgRating'
        ));
    }
}
