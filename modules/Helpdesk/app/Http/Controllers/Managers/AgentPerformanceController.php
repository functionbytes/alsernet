<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\CsatRating;

class AgentPerformanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.reports.view');
    }

    public function index(): View
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $agentRows = DB::connection('helpdesk')
            ->table('helpdesk_conversations as c')
            ->whereBetween('c.closed_at', [$from, $to])
            ->whereNotNull('c.assignee_id')
            ->select([
                'c.assignee_id',
                DB::connection('helpdesk')->raw('COUNT(*) as closed_count'),
                DB::connection('helpdesk')->raw('AVG(CASE WHEN c.first_response_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, c.created_at, c.first_response_at) END) as avg_response_sec'),
            ])
            ->groupBy('c.assignee_id')
            ->get();

        $agentIds = $agentRows->pluck('assignee_id')->all();

        $csatByAgent = CsatRating::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('answered_at', [$from, $to])
            ->whereNotNull('answered_at')
            ->selectRaw('agent_id, AVG(rating) as csat_avg')
            ->groupBy('agent_id')
            ->pluck('csat_avg', 'agent_id');

        $messagesByAgent = DB::connection('helpdesk')
            ->table('helpdesk_conversation_items as ci')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'ci.conversation_id')
            ->whereBetween('ci.created_at', [$from, $to])
            ->whereNotNull('ci.user_id')
            ->where('ci.type', 'message')
            ->select(['ci.user_id', DB::connection('helpdesk')->raw('COUNT(*) as msg_count')])
            ->groupBy('ci.user_id')
            ->pluck('msg_count', 'user_id');

        $users = User::whereIn('id', $agentIds)->select(['id', 'firstname', 'lastname'])->get()->keyBy('id');

        $agents = $agentRows->map(function ($row) use ($users, $csatByAgent, $messagesByAgent) {
            $user = $users->get($row->assignee_id);

            return [
                'name' => $user ? trim("{$user->firstname} {$user->lastname}") : "Agente #{$row->assignee_id}",
                'closed_count' => (int) $row->closed_count,
                'csat_avg' => round((float) ($csatByAgent[$row->assignee_id] ?? 0), 2),
                'avg_response_seconds' => (int) round($row->avg_response_sec ?? 0),
                'message_count' => (int) ($messagesByAgent[$row->assignee_id] ?? 0),
            ];
        })->sortByDesc('closed_count')->values();

        return view('helpdesk::managers.helpdesk.reports.agents', compact('agents', 'from', 'to'));
    }
}
