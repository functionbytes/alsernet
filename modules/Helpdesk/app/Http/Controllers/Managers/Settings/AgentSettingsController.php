<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\UpdateAgentSettingsRequest;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Skill;

class AgentSettingsController extends Controller
{
    /**
     * Roles considered helpdesk agents. Kept in sync with
     * AgentsController so both panels list the same users.
     *
     * @var list<string>
     */
    private const AGENT_ROLES = ['administrative', 'manager', 'settings', 'super-settings'];

    /**
     * Display all agents with their settings.
     */
    public function index(Request $request): View
    {
        $this->authorize('helpdesk.agents.manage');

        $query = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::AGENT_ROLES));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('available')) {
            $isAvailable = $request->available === '1';
            $query->whereHas('agentSettings', fn ($q) => $q->where('is_available', $isAvailable));
        }

        $agents = $query->orderBy('firstname')->paginate(25)->appends($request->query());

        // Load agent settings and skills separately (cross-DB safe)
        $agentIds = $agents->pluck('id');

        $settingsMap = AgentSettings::query()
            ->whereIn('user_id', $agentIds)
            ->get()
            ->keyBy('user_id');

        $skillsMap = DB::connection('helpdesk')
            ->table('helpdesk_user_skills as us')
            ->join('helpdesk_skills as s', 's.id', '=', 'us.skill_id')
            ->whereIn('us.user_id', $agentIds)
            ->select('us.user_id', 's.id as skill_id', 's.name as skill_name')
            ->get()
            ->groupBy('user_id');

        // Stats
        $stats = $this->getStats();

        return view('helpdesk::settings.agent-settings.index', [
            'agents' => $agents,
            'settingsMap' => $settingsMap,
            'skillsMap' => $skillsMap,
            'stats' => $stats,
        ]);
    }

    /**
     * Show edit form for a specific agent.
     */
    public function edit(User $user): View
    {
        $this->authorize('helpdesk.agents.manage');

        $agentSettings = AgentSettings::query()
            ->where('user_id', $user->id)
            ->first() ?? AgentSettings::newFromDefault();

        $skills = Skill::orderBy('name')->get();

        $assignedSkillIds = DB::connection('helpdesk')
            ->table('helpdesk_user_skills')
            ->where('user_id', $user->id)
            ->pluck('skill_id')
            ->toArray();

        return view('helpdesk::settings.agent-settings.edit', [
            'agent' => $user,
            'agentSettings' => $agentSettings,
            'skills' => $skills,
            'assignedSkillIds' => $assignedSkillIds,
        ]);
    }

    /**
     * Update agent settings and sync skills.
     */
    public function update(UpdateAgentSettingsRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::connection('helpdesk')->transaction(function () use ($validated, $user) {
            AgentSettings::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'is_available' => $request->boolean('is_available'),
                    'accepts_conversations' => $validated['accepts_conversations'],
                    'max_concurrent_conversations' => $validated['max_concurrent_conversations'] ?? 0,
                    'auto_assign' => $request->boolean('auto_assign'),
                    'vacation_until' => $validated['vacation_until'] ?? null,
                ]
            );

            DB::connection('helpdesk')
                ->table('helpdesk_user_skills')
                ->where('user_id', $user->id)
                ->delete();

            if (! empty($validated['skills'])) {
                $inserts = array_map(
                    fn ($skillId) => ['user_id' => $user->id, 'skill_id' => $skillId, 'proficiency' => 1],
                    $validated['skills']
                );
                DB::connection('helpdesk')->table('helpdesk_user_skills')->insert($inserts);
            }
        });

        return redirect()
            ->route('settings.helpdesk.agent-settings.index')
            ->with('success', "Configuracion de {$user->name} actualizada correctamente");
    }

    /**
     * Calculate summary statistics for the index page.
     */
    private function getStats(): array
    {
        $total = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::AGENT_ROLES))
            ->count();

        $available = AgentSettings::where('is_available', true)->count();

        $onVacation = AgentSettings::whereNotNull('vacation_until')
            ->where('vacation_until', '>', now())
            ->count();

        $withLimit = AgentSettings::where('max_concurrent_conversations', '>', 0)->count();

        return [
            'total' => $total,
            'available' => $available,
            'on_vacation' => $onVacation,
            'with_limit' => $withLimit,
        ];
    }
}
