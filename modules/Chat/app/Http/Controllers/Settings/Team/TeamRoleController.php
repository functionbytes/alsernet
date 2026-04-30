<?php

namespace Modules\Chat\Http\Controllers\Settings\Team;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Teams\TeamRole;

class TeamRoleController extends Controller
{
    /**
     * Display a listing of team roles.
     */
    public function index(Request $request)
    {
        $roles = auth()->user()->account->teamRoles()
            ->withCount('users')
            ->when($request->get('search'), function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('Chat::settings.team-roles.index', compact('roles', 'availablePermissions'));
    }

    /**
     * Show the form for creating a new team role.
     */
    public function create()
    {
        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('Chat::settings.team-roles.create', compact('availablePermissions'));
    }

    /**
     * Store a newly created team role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_default' => 'boolean',
        ]);

        auth()->user()->account->teamRoles()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('settings.chat.team-roles.index')
            ->with('success', 'Team role created successfully.');
    }

    /**
     * Display the specified team role.
     */
    public function show(TeamRole $teamRole)
    {
        $this->authorize('view', $teamRole);

        $teamRole->loadCount('users');
        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('Chat::settings.team-roles.show', compact('teamRole', 'availablePermissions'));
    }

    /**
     * Show the form for editing the specified team role.
     */
    public function edit(TeamRole $teamRole)
    {
        $this->authorize('update', $teamRole);

        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('Chat::settings.team-roles.edit', compact('teamRole', 'availablePermissions'));
    }

    /**
     * Update the specified team role.
     */
    public function update(Request $request, TeamRole $teamRole)
    {
        $this->authorize('update', $teamRole);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_default' => 'boolean',
        ]);

        $teamRole->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('settings.chat.team-roles.index')
            ->with('success', 'Team role updated successfully.');
    }

    /**
     * Remove the specified team role.
     */
    public function destroy(TeamRole $teamRole)
    {
        $this->authorize('delete', $teamRole);

        $teamRole->delete();

        return redirect()->route('settings.chat.team-roles.index')
            ->with('success', 'Team role deleted successfully.');
    }
}
