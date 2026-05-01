<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\UpdateAgentSettingsRequest;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Group;
use Modules\HelpdeskTickets\Models\Ticket;

class AgentsController extends Controller
{
    public function index(): View
    {
        $this->authorize('helpdesk.manage');

        $agents = User::role(['administrative', 'manager', 'settings', 'super-settings'])
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->with(['agentSettings', 'roles:id,name'])
            ->orderBy('firstname')
            ->paginate(20);

        return view('helpdesk::managers.agents.index', compact('agents'));
    }

    /**
     * Lightweight JSON endpoint used by the @mention picker in the composer.
     * Devuelve usuarios mencionables enriquecidos con role, skills y workload.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));
        $sessionThreshold = now()->subMinutes(5)->timestamp;

        // Permite incluir al usuario actual con ?include_self=1 para fallback de demo
        $includeSelf = $request->boolean('include_self');

        $agents = User::query()
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->with([
                'roles:id,name',
                'agentSettings:id,user_id,assignment_limit,accepts_conversations',
                'groups:id,name',
            ])
            ->when(! $includeSelf, fn ($q) => $q->where('id', '!=', auth()->id()))
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($w) use ($term) {
                    $w->where('firstname', 'like', "%{$term}%")
                        ->orWhere('lastname', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('firstname')
            ->limit(12)
            ->get();

        $onlineIds = \DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $sessionThreshold)
            ->pluck('user_id')
            ->unique()
            ->all();

        // Workload actual por agente: conversaciones abiertas (no archivadas, status open)
        $agentIds = $agents->pluck('id');
        $workloads = Conversation::query()
            ->whereIn('assignee_id', $agentIds)
            ->where('is_archived', false)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->selectRaw('assignee_id, COUNT(*) as total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id');

        $data = $agents->map(function (User $u) use ($onlineIds, $workloads) {
            $name = trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: $u->email;
            $initials = collect(preg_split('/\s+/', $name))
                ->filter()
                ->take(2)
                ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                ->implode('');

            $isOnline = in_array($u->id, $onlineIds, true);
            $accepts = $u->agentSettings?->accepts_conversations ?? 'yes';
            $limit = (int) ($u->agentSettings?->assignment_limit ?? 0);
            $current = (int) ($workloads[$u->id] ?? 0);
            $maxWorkload = $limit > 0 ? $limit : 15;

            // Status: away (no acepta), offline (sin sesión), busy (al límite), online
            if ($accepts === 'no') {
                $status = 'away';
                $statusLabel = 'Ausente';
            } elseif (! $isOnline) {
                $status = 'offline';
                $statusLabel = 'Offline';
            } elseif ($limit > 0 && $current >= $limit) {
                $status = 'busy';
                $statusLabel = 'Ocupado';
            } else {
                $status = 'online';
                $statusLabel = 'En línea';
            }

            return [
                'id' => $u->id,
                'name' => $name,
                'username' => str_replace(' ', '.', mb_strtolower($name)),
                'email' => $u->email,
                'initials' => $initials ?: '?',
                'online' => $isOnline,
                'status' => $status,
                'status_label' => $statusLabel,
                'role' => $u->roles->first()?->name,
                'skills' => $u->groups->pluck('name')->take(3)->all(),
                'workload_current' => $current,
                'workload_max' => $maxWorkload,
            ];
        });

        // Equipos (groups) — aparecen junto a agentes en el modal de mención
        $teams = Group::query()
            ->where('is_active', true)
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($w) use ($term) {
                    $w->where('name', 'like', "%{$term}%")
                        ->orWhere('key', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->withCount('users')
            ->orderBy('position')
            ->orderBy('name')
            ->limit(12)
            ->get();

        $teamIds = $teams->pluck('id');
        $teamWorkloads = Conversation::query()
            ->whereIn('group_id', $teamIds)
            ->where('is_archived', false)
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->selectRaw('group_id, COUNT(*) as total')
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $teamsData = $teams->map(function (Group $g) use ($teamWorkloads) {
            $current = (int) ($teamWorkloads[$g->id] ?? 0);
            $members = (int) ($g->users_count ?? 0);

            return [
                'id' => $g->id,
                'name' => $g->name,
                'key' => $g->key,
                'description' => $g->description,
                'members_count' => $members,
                'workload_current' => $current,
                'workload_max' => max($members * 5, 10), // heurística: 5 conversaciones/agente
            ];
        });

        return response()->json([
            'agents' => $data,
            'teams' => $teamsData,
        ]);
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

        return view('helpdesk::managers.agents.show', compact('agent', 'stats', 'recentTickets'));
    }

    public function edit(User $agent): View
    {
        $this->authorize('helpdesk.manage');

        $agentSettings = $agent->agentSettings ?? AgentSettings::newFromDefault();

        return view('helpdesk::managers.agents.edit', compact('agent', 'agentSettings'));
    }

    public function update(UpdateAgentSettingsRequest $request, User $agent): RedirectResponse
    {
        $validated = $request->validated();

        $validated['working_hours'] = $this->buildWorkingHours($validated);

        if (empty($validated['vacation_until'])) {
            $validated['vacation_until'] = null;
        }

        $agent->agentSettings()->updateOrCreate(
            ['user_id' => $agent->id],
            $validated
        );

        return back()->with('success', 'Configuración del agente actualizada.');
    }

    private function buildWorkingHours(array $validated): ?array
    {
        if ($validated['accepts_conversations'] !== 'working_hours') {
            return null;
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $input = $validated['working_hours'] ?? [];
        $hours = [];

        foreach ($days as $day) {
            $day_data = $input[$day] ?? [];
            $hours[$day] = [
                'enabled' => (bool) ($day_data['enabled'] ?? false),
                'start' => $day_data['start'] ?? '09:00',
                'end' => $day_data['end'] ?? '18:00',
            ];
        }

        return $hours;
    }
}
