<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $groups = Group::orderBy('name')->get();
        $roles = Role::whereIn('name', ['admin', 'manager', 'support', 'callcenter'])->get();

        // Get all members for statistics (without pagination)
        $allMembers = User::query()
            ->with(['roles', 'agentSettings', 'groups'])
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager', 'support', 'callcenter']);
            })
            ->get();

        // Calculate statistics
        $stats = [
            'total' => $allMembers->count(),
            'available' => $allMembers->filter(fn ($m) => ($m->agentSettings->accepts_conversations ?? 'no') === 'yes')->count(),
            'working_hours' => $allMembers->filter(fn ($m) => ($m->agentSettings->accepts_conversations ?? 'no') === 'working_hours')->count(),
            'unavailable' => $allMembers->filter(fn ($m) => ($m->agentSettings->accepts_conversations ?? 'no') === 'no')->count(),
            'admin' => $allMembers->filter(fn ($m) => $m->roles->contains('name', 'admin'))->count(),
            'manager' => $allMembers->filter(fn ($m) => $m->roles->contains('name', 'manager'))->count(),
            'support' => $allMembers->filter(fn ($m) => $m->roles->contains('name', 'support'))->count(),
            'callcenter' => $allMembers->filter(fn ($m) => $m->roles->contains('name', 'callcenter'))->count(),
            'with_unlimited' => $allMembers->filter(fn ($m) => ($m->agentSettings->assignment_limit ?? 0) === 0)->count(),
            'with_limit' => $allMembers->filter(fn ($m) => ($m->agentSettings->assignment_limit ?? 0) > 0)->count(),
        ];

        return view('helpdesk::managers.settings.helpdesk.team.members', [
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

        $groups = Group::orderBy('name')->get();
        $roles = Role::whereIn('name', ['admin', 'manager', 'support', 'callcenter'])->get();

        // Ensure agent backups exist
        if (! $member->agentSettings) {
            $member->setRelation('agentSettings', AgentSettings::newFromDefault());
        }

        return view('helpdesk::managers.settings.helpdesk.team.member-edit', [
            'member' => $member,
            'groups' => $groups,
            'roles' => $roles,
        ]);
    }

    /**
     * Update member backups.
     */
    public function memberUpdate(Request $request, $id)
    {
        $member = User::findOrFail($id);

        $this->authorize('update', $member);

        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'assignment_limit' => 'nullable|integer|min:0',
            'accepts_conversations' => 'required|in:yes,no,working_hours',
            'working_hours' => 'nullable|array',
            'groups' => 'nullable|array',
            'groups.*' => 'exists:helpdesk_groups,id',
            'role' => 'nullable|exists:roles,name',
        ]);

        // Update user basic info
        $member->update([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
        ]);

        // Update or create agent backups
        $member->agentSettings()->updateOrCreate(
            ['user_id' => $member->id],
            [
                'assignment_limit' => $validated['assignment_limit'] ?? 0,
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
            ->route('manager.helpdesk.settings.team.members')
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

        // Get all groups for statistics
        $allGroups = Group::with('users')->get();

        // Calculate statistics
        $stats = [
            'total' => $allGroups->count(),
            'default' => $allGroups->where('default', true)->count(),
            'with_members' => $allGroups->filter(fn ($g) => $g->users->count() > 0)->count(),
            'empty' => $allGroups->filter(fn ($g) => $g->users->count() === 0)->count(),
            'total_members' => $allGroups->sum(fn ($g) => $g->users->count()),
            'primary_members' => $allGroups->sum(fn ($g) => $g->users->where('pivot.conversation_priority', 'primary')->count()),
            'backup_members' => $allGroups->sum(fn ($g) => $g->users->where('pivot.conversation_priority', 'backup')->count()),
            'round_robin' => $allGroups->where('assignment_mode', 'round_robin')->count(),
            'load_balance' => $allGroups->where('assignment_mode', 'load_balance')->count(),
            'priority' => $allGroups->where('assignment_mode', 'priority')->count(),
        ];

        return view('helpdesk::managers.settings.helpdesk.team.groups', [
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
        })->orderBy('firstname')->get();

        return view('helpdesk::managers.settings.helpdesk.team.group-create', [
            'agents' => $agents,
        ]);
    }

    /**
     * Store new group.
     */
    public function groupStore(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'assignment_mode' => 'required|in:round_robin,load_balanced,manual',
            'default' => 'nullable|boolean',
            'members' => 'required|array|min:1',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.priority' => 'required|in:primary,backup',
        ], [
            'members.required' => 'Debe agregar al menos un miembro al grupo',
            'members.min' => 'Debe agregar al menos un miembro al grupo',
        ]);

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
            ->route('manager.helpdesk.settings.ticket-groups.index')
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
        })->orderBy('firstname')->get();

        return view('helpdesk::managers.settings.helpdesk.team.group-edit', [
            'group' => $group,
            'agents' => $agents,
        ]);
    }

    /**
     * Update group.
     */
    public function groupUpdate(Request $request, $id)
    {
        $this->authorize('update', User::class);

        $group = Group::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'assignment_mode' => 'required|in:round_robin,load_balanced,manual',
            'default' => 'nullable|boolean',
            'members' => 'required|array|min:1',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.priority' => 'required|in:primary,backup',
        ], [
            'members.required' => 'Debe agregar al menos un miembro al grupo',
            'members.min' => 'Debe agregar al menos un miembro al grupo',
        ]);

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
            ->route('manager.helpdesk.settings.ticket-groups.index')
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
            ->route('manager.helpdesk.settings.ticket-groups.index')
            ->with('success', 'Grupo eliminado correctamente');
    }
}
