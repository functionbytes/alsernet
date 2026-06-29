<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Http\Requests\Managers\Settings\UpdateTeamMemberRequest;
use Modules\Helpdesk\Http\Requests\StoreGroupRequest;
use Modules\Helpdesk\Http\Requests\UpdateGroupRequest;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Group;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    /**
     * Display team members list.
     */
    public function membersIndex(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->with(['roles', 'agentSettings', 'groups'])
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager', 'support', 'callcenter']);
            });

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by group
        if ($request->has('group_id') && $request->group_id !== 'all') {
            $query->whereHas('groups', function ($q) use ($request) {
                $q->where('helpdesk_groups.id', $request->group_id);
            });
        }

        // Sort and paginate
        $members = $query
            ->orderBy('firstname', 'asc')
            ->paginate(50)
            ->appends($request->query());

        $groups = Group::orderBy('name')->limit(200)->get();
        $roles = Role::whereIn('name', ['admin', 'manager', 'support', 'callcenter'])->limit(200)->get();

        // Calculate statistics via SQL (avoid loading all members into memory)
        $baseUserQuery = User::query()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager', 'support', 'callcenter']);
        });

        $total = (int) (clone $baseUserQuery)->count();

        $availability = User::query()
            ->join('helpdesk_agent_settings', 'users.id', '=', 'helpdesk_agent_settings.user_id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('model_has_roles')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->whereIn('model_has_roles.role_id', function ($sq) {
                        $sq->select('id')->from('roles')->whereIn('name', ['admin', 'manager', 'support', 'callcenter']);
                    });
            })
            ->selectRaw('
                SUM(CASE WHEN helpdesk_agent_settings.accepts_conversations = ? THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN helpdesk_agent_settings.accepts_conversations = ? THEN 1 ELSE 0 END) as working_hours,
                SUM(CASE WHEN helpdesk_agent_settings.accepts_conversations = ? THEN 1 ELSE 0 END) as unavailable,
                SUM(CASE WHEN helpdesk_agent_settings.max_concurrent_conversations = 0 THEN 1 ELSE 0 END) as with_unlimited,
                SUM(CASE WHEN helpdesk_agent_settings.max_concurrent_conversations > 0 THEN 1 ELSE 0 END) as with_limit
            ', ['yes', 'working_hours', 'no'])
            ->first();

        $roleCounts = Role::query()
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['admin', 'manager', 'support', 'callcenter'])
            ->where('model_has_roles.model_type', User::class)
            ->selectRaw('roles.name, COUNT(*) as count')
            ->groupBy('roles.name')
            ->pluck('count', 'roles.name')
            ->toArray();

        $stats = [
            'total' => $total,
            'available' => (int) ($availability->available ?? 0),
            'working_hours' => (int) ($availability->working_hours ?? 0),
            'unavailable' => (int) ($availability->unavailable ?? 0),
            'admin' => (int) ($roleCounts['admin'] ?? 0),
            'manager' => (int) ($roleCounts['manager'] ?? 0),
            'support' => (int) ($roleCounts['support'] ?? 0),
            'callcenter' => (int) ($roleCounts['callcenter'] ?? 0),
            'with_unlimited' => (int) ($availability->with_unlimited ?? 0),
            'with_limit' => (int) ($availability->with_limit ?? 0),
        ];

        return view('helpdesk::settings.team.members', [
            'members' => $members,
            'groups' => $groups,
            'roles' => $roles,
            'stats' => $stats,
        ]);
    }

    /**
     * Show member edit form.
     */
    public function memberEdit($id)
    {
        $member = User::with(['roles', 'agentSettings', 'groups'])->findOrFail($id);

        $this->authorize('update', $member);

        $groups = Group::orderBy('name')->limit(200)->get();
        $roles = Role::whereIn('name', ['admin', 'manager', 'support', 'callcenter'])->limit(200)->get();

        // Ensure agent backups exist
        if (! $member->agentSettings) {
            $member->setRelation('agentSettings', AgentSettings::newFromDefault());
        }

        return view('helpdesk::settings.team.member-edit', [
            'member' => $member,
            'groups' => $groups,
            'roles' => $roles,
        ]);
    }

    /**
     * Update member backups.
     */
    public function memberUpdate(UpdateTeamMemberRequest $request, $id)
    {
        $member = User::findOrFail($id);

        $this->authorize('update', $member);

        $validated = $request->validated();

        // Update user basic info
        $member->update([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
        ]);

        // Update or create agent settings
        $member->agentSettings()->updateOrCreate(
            ['user_id' => $member->id],
            [
                'max_concurrent_conversations' => $validated['max_concurrent_conversations'] ?? 0,
                'accepts_conversations' => $validated['accepts_conversations'],
                'working_hours' => $validated['working_hours'] ?? null,
            ]
        );

        // Update groups with priority
        if (isset($validated['groups'])) {
            $groupsData = collect($validated['groups'])->mapWithKeys(function ($groupId) use ($request) {
                return [
                    $groupId => [
                        'conversation_priority' => $request->input("group_priority.{$groupId}", 'backup'),
                    ],
                ];
            });
            $member->groups()->sync($groupsData);
        } else {
            $member->groups()->detach();
        }

        // Update role if provided
        if (isset($validated['role'])) {
            $member->syncRoles([$validated['role']]);
        }

        return redirect()
            ->route('settings.helpdesk.team.members')
            ->with('success', "Configuración de {$member->name} actualizada correctamente");
    }

    /**
     * Display groups list.
     */
    public function groupsIndex(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = Group::query()->with('users');

        // Apply search
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $groups = $query
            ->orderBy('default', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(50)
            ->appends($request->query());

        // Calculate statistics via SQL
        $groupStats = Group::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN `default` = 1 THEN 1 ELSE 0 END) as `default`,
            SUM(CASE WHEN assignment_mode = ? THEN 1 ELSE 0 END) as round_robin,
            SUM(CASE WHEN assignment_mode = ? THEN 1 ELSE 0 END) as load_balance,
            SUM(CASE WHEN assignment_mode = ? THEN 1 ELSE 0 END) as priority
        ', ['round_robin', 'load_balance', 'priority'])->first();

        $memberStats = Group::query()
            ->leftJoin('helpdesk_group_user', 'helpdesk_groups.id', '=', 'helpdesk_group_user.group_id')
            ->selectRaw('
                SUM(CASE WHEN helpdesk_group_user.user_id IS NOT NULL THEN 1 ELSE 0 END) as total_members,
                SUM(CASE WHEN helpdesk_group_user.conversation_priority = ? THEN 1 ELSE 0 END) as primary_members,
                SUM(CASE WHEN helpdesk_group_user.conversation_priority = ? THEN 1 ELSE 0 END) as backup_members,
                COUNT(DISTINCT CASE WHEN helpdesk_group_user.user_id IS NOT NULL THEN helpdesk_groups.id END) as with_members,
                COUNT(DISTINCT CASE WHEN helpdesk_group_user.user_id IS NULL THEN helpdesk_groups.id END) as empty
            ', ['primary', 'backup'])
            ->first();

        $stats = [
            'total' => (int) $groupStats->total,
            'default' => (int) $groupStats->default,
            'with_members' => (int) $memberStats->with_members,
            'empty' => (int) $memberStats->empty,
            'total_members' => (int) $memberStats->total_members,
            'primary_members' => (int) $memberStats->primary_members,
            'backup_members' => (int) $memberStats->backup_members,
            'round_robin' => (int) $groupStats->round_robin,
            'load_balance' => (int) $groupStats->load_balance,
            'priority' => (int) $groupStats->priority,
        ];

        return view('helpdesk::settings.team.groups', [
            'groups' => $groups,
            'stats' => $stats,
        ]);
    }

    /**
     * Show create group form.
     */
    public function groupCreate()
    {
        $this->authorize('create', User::class);

        $agents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager', 'support', 'callcenter']);
        })->orderBy('firstname')->limit(200)->get();

        return view('helpdesk::settings.team.group-create', [
            'agents' => $agents,
        ]);
    }

    /**
     * Store new group.
     */
    public function groupStore(StoreGroupRequest $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        DB::connection('helpdesk')->transaction(function () use ($validated) {
            $group = Group::create([
                'name' => $validated['name'],
                'assignment_mode' => $validated['assignment_mode'],
                'default' => $validated['default'] ?? false,
            ]);

            // Attach members with priority
            $members = collect($validated['members'])->mapWithKeys(function ($member) {
                return [
                    $member['user_id'] => [
                        'conversation_priority' => $member['priority'],
                    ],
                ];
            });

            $group->users()->attach($members);
        });

        return redirect()
            ->route('settings.helpdesk.team.groups')
            ->with('success', 'Grupo creado correctamente');
    }

    /**
     * Show edit group form.
     */
    public function groupEdit($id)
    {
        $this->authorize('update', User::class);

        $group = Group::with('users')->findOrFail($id);

        $agents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager', 'support', 'callcenter']);
        })->orderBy('firstname')->limit(200)->get();

        return view('helpdesk::settings.team.group-edit', [
            'group' => $group,
            'agents' => $agents,
        ]);
    }

    /**
     * Update group.
     */
    public function groupUpdate(UpdateGroupRequest $request, $id)
    {
        $this->authorize('update', User::class);

        $group = Group::findOrFail($id);

        $validated = $request->validated();

        DB::connection('helpdesk')->transaction(function () use ($group, $validated) {
            $group->update([
                'name' => $validated['name'],
                'assignment_mode' => $validated['assignment_mode'],
                'default' => $validated['default'] ?? false,
            ]);

            // Sync members with priority
            $members = collect($validated['members'])->mapWithKeys(function ($member) {
                return [
                    $member['user_id'] => [
                        'conversation_priority' => $member['priority'],
                    ],
                ];
            });

            $group->users()->sync($members);
        });

        return redirect()
            ->route('settings.helpdesk.team.groups')
            ->with('success', 'Grupo actualizado correctamente');
    }

    /**
     * Delete group.
     */
    public function groupDestroy($id)
    {
        $this->authorize('delete', User::class);

        $group = Group::findOrFail($id);

        $group->users()->detach();
        $group->delete();

        return redirect()
            ->route('settings.helpdesk.team.groups')
            ->with('success', 'Grupo eliminado correctamente');
    }
}
