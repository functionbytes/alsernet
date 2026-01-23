<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Team;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Teams\TeamRole;

class TeamRoleController extends Controller
{
    /**
     * Display a listing of team roles.
     */
    public function index()
    {
        $roles = auth()->user()->account->teamRoles()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('helpdeskchat::admin.settings.team-roles.index', compact('roles', 'availablePermissions'));
    }

    /**
     * Show the form for creating a new team role.
     */
    public function create()
    {
        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('helpdeskchat::admin.settings.team-roles.create', compact('availablePermissions'));
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

        return redirect()->route('admin.team-roles.index')
            ->with('success', 'Team role created successfully.');
    }

    /**
     * Display the specified team role.
     */
    public function show(TeamRole $teamRole)
    {
        $this->authorize('view', $teamRole);

        $teamRole->load('users');
        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('helpdeskchat::admin.settings.team-roles.show', compact('teamRole', 'availablePermissions'));
    }

    /**
     * Show the form for editing the specified team role.
     */
    public function edit(TeamRole $teamRole)
    {
        $this->authorize('update', $teamRole);

        $availablePermissions = TeamRole::getAvailablePermissions();

        return view('helpdeskchat::admin.settings.team-roles.edit', compact('teamRole', 'availablePermissions'));
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

        return redirect()->route('admin.team-roles.index')
            ->with('success', 'Team role updated successfully.');
    }

    /**
     * Remove the specified team role.
     */
    public function destroy(TeamRole $teamRole)
    {
        $this->authorize('delete', $teamRole);

        // Prevent deletion if role is assigned to users
        if ($teamRole->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role that is assigned to users.');
        }

        $teamRole->delete();

        return redirect()->route('admin.team-roles.index')
            ->with('success', 'Team role deleted successfully.');
    }
}
