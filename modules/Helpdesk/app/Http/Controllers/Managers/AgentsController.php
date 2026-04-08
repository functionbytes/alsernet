<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Ticket;

class AgentsController extends Controller
{
    public function index(): View
    {
        $agents = User::role('helpdesk-agent')
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->with('agentSettings')
            ->orderBy('firstname')
            ->paginate(20);

        return view('helpdesk::managers.helpdesk.agents.index', compact('agents'));
    }

    public function show(User $agent): View
    {
        $stats = [
            'open' => Ticket::where('assignee_id', $agent->id)->whereNull('closed_at')->count(),
            'closed_this_month' => Ticket::where('assignee_id', $agent->id)->whereMonth('closed_at', now()->month)->count(),
            'sla_breached' => Ticket::where('assignee_id', $agent->id)->where('sla_resolution_breached', true)->whereNull('closed_at')->count(),
            'avg_rating' => round(Ticket::where('assignee_id', $agent->id)->whereNotNull('rated_at')->avg('rating') ?? 0, 1),
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
        $agentSettings = $agent->agentSettings ?? AgentSettings::newFromDefault();

        return view('helpdesk::managers.helpdesk.agents.edit', compact('agent', 'agentSettings'));
    }

    public function update(Request $request, User $agent): RedirectResponse
    {
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
