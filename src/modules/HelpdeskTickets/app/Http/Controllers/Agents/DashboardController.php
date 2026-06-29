<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskTickets\Models\Ticket;

class DashboardController extends Controller
{
    public function index(): View
    {
        $agentId = auth()->id();

        $stats = Cache::remember("helpdesk:agent:dashboard:{$agentId}", 60, function () use ($agentId) {
            $ticketStats = Ticket::where('assignee_id', $agentId)
                ->selectRaw('SUM(closed_at IS NULL) as my_open')
                ->selectRaw('SUM(sla_resolution_breached = 1 AND closed_at IS NULL) as my_sla_breached')
                ->selectRaw('SUM(DATE(sla_resolution_due_at) = CURDATE() AND closed_at IS NULL) as my_due_today')
                ->selectRaw('SUM(DATE(closed_at) = CURDATE()) as my_closed_today')
                ->first();

            return [
                'my_open' => (int) $ticketStats->my_open,
                'my_sla_breached' => (int) $ticketStats->my_sla_breached,
                'my_due_today' => (int) $ticketStats->my_due_today,
                'my_closed_today' => (int) $ticketStats->my_closed_today,
                'open_conversations' => (int) Conversation::where('assignee_id', $agentId)->whereNull('closed_at')->count(),
            ];
        });

        $recentTickets = Ticket::where('assignee_id', $agentId)
            ->whereNull('closed_at')
            ->with(['status:id,name,color', 'category:id,name', 'customer:id,name'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $urgentTickets = Ticket::where('assignee_id', $agentId)
            ->where('sla_resolution_breached', true)
            ->whereNull('closed_at')
            ->with(['status:id,name,color', 'customer:id,name'])
            ->orderBy('sla_resolution_due_at')
            ->limit(5)
            ->get();

        return view('helpdesk::agents.dashboard', compact('stats', 'recentTickets', 'urgentTickets'));
    }
}
