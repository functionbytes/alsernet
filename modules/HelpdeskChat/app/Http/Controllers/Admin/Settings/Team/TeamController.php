<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Team;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Teams\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = auth()->user()->account->teams()
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20);

        return view('helpdeskchat::admin.settings.teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = auth()->user()->account->users;

        return view('helpdeskchat::admin.settings.teams.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allow_auto_assign' => 'boolean',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $team = auth()->user()->account->teams()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'allow_auto_assign' => $validated['allow_auto_assign'] ?? true,
        ]);

        if (! empty($validated['member_ids'])) {
            $team->members()->attach($validated['member_ids']);
        }

        return redirect()->route('admin.helpdesk.teams.index')
            ->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);

        $team->load(['members', 'conversation' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('helpdeskchat::admin.settings.teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $this->authorize('update', $team);

        $users = auth()->user()->account->users;
        $team->load('members');

        return view('helpdeskchat::admin.settings.teams.edit', compact('team', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allow_auto_assign' => 'boolean',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $team->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'allow_auto_assign' => $validated['allow_auto_assign'] ?? $team->allow_auto_assign,
        ]);

        if (isset($validated['member_ids'])) {
            $team->members()->sync($validated['member_ids']);
        }

        return redirect()->route('admin.helpdesk.teams.index')
            ->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('admin.helpdesk.teams.index')
            ->with('success', 'Team deleted successfully.');
    }
}
