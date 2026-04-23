<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\HelpdeskTickets\Models\Ticket;

class AgentsController extends Controller
{
    public function index(): View
    {
        $this->authorize('helpdesk.manage');

        $agents = User::role('helpdesk-agent')
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->with('agentSettings')
            ->orderBy('firstname')
            ->paginate(20);

        return view('helpdesk::managers.helpdesk.agents.index', compact('agents'));
    }

    public function show(User $agent): View
    {
        $this->authorize('helpdesk.manage');

        $row = Ticket::where('assignee_id', $agent->id)
            ->selectRaw('
                SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN MONTH(closed_at) = ? THEN 1 ELSE 0 END) as closed_this_month,
                SUM(CASE WHEN sla_resolution_breached = 1 AND closed_at IS NULL THEN 1 ELSE 0 END) as sla_breached,
                AVG(CASE WHEN rated_at IS NOT NULL THEN rating END) as avg_rating
            ', [now()->month])
            ->first();

        $stats = [
            'open' => (int) $row->open,
            'closed_this_month' => (int) $row->closed_this_month,
            'sla_breached' => (int) $row->sla_breached,
            'avg_rating' => round($row->avg_rating ?? 0, 1),
        ];

        $recentTickets = Ticket::where('assignee_id', $agent->id)
            ->with(['status:id,name,color', 'customer:id,name'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('helpdesk::managers.helpdesk.agents.show', compact('agent', 'stats', 'recentTickets'));
    }

    public function edit(User $agent): View
    {
        $this->authorize('helpdesk.manage');

        $agentSettings = $agent->agentSettings ?? AgentSettings::newFromDefault();

        return view('helpdesk::managers.helpdesk.agents.edit', compact('agent', 'agentSettings'));
    }

    public function update(Request $request, User $agent): RedirectResponse
    {
        $this->authorize('helpdesk.manage');

        $validated = $request->validate([
            'accepts_conversations' => ['required', 'in:yes,no,working_hours'],
            'max_concurrent_conversations' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $agent->agentSettings()->updateOrCreate(
            ['user_id' => $agent->id],
            $validated
        );

        return back()->with('success', 'Configuración del agente actualizada.');
    }
}
