<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskTickets\Models\Ticket;

class DashboardController extends Controller
{
    public function index(): View
    {
        $agentId = auth()->id();

        $stats = [
            'my_open' => Ticket::where('assignee_id', $agentId)->whereNull('closed_at')->count(),
            'my_sla_breached' => Ticket::where('assignee_id', $agentId)
                ->where('sla_resolution_breached', true)
                ->whereNull('closed_at')
                ->count(),
            'my_due_today' => Ticket::where('assignee_id', $agentId)
                ->whereNull('closed_at')
                ->whereDate('sla_resolution_due_at', today())
                ->count(),
            'my_closed_today' => Ticket::where('assignee_id', $agentId)
                ->whereDate('closed_at', today())
                ->count(),
            'open_conversations' => Conversation::where('assignee_id', $agentId)->whereNull('closed_at')->count(),
        ];

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
