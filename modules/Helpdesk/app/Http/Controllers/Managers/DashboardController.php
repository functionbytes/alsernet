<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;

class DashboardController extends Controller
{
    public function index(TicketServiceContract $tickets): View
    {
        $this->authorize('access_helpdesk');

        $ticketData = $tickets->getDashboardData();

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

        return view('helpdesk::helpdesk.dashboard', [
            'ticketStats' => $ticketData['stats'],
            'convStats' => $convStats,
            'agentStats' => $ticketData['topAgents'],
            'recentBreaches' => $ticketData['recentBreaches'],
            'recentTickets' => $ticketData['recentTickets'],
            'avgRating' => $ticketData['avgRating'],
        ]);
    }
}
