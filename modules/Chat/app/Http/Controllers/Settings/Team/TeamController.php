<?php

namespace Modules\Chat\Http\Controllers\Settings\Team;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Teams\Team;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $account = auth()->user()->account;

        $query = $account->teams();

        if ($request->input('tab') === 'trashed') {
            $query->onlyTrashed();
        }

        $query->withCount('members')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $teams = $query->paginate(20);

        $aggregates = $account->teams()
            ->selectRaw('
                COUNT(*) as total,
                SUM(allow_auto_assign = 1) as with_auto_assign,
                SUM(allow_auto_assign = 0) as without_auto_assign,
                (
                    SELECT COUNT(*)
                    FROM chat_team_user htu
                    INNER JOIN users u ON u.id = htu.user_id
                    WHERE htu.team_id IN (SELECT id FROM chat_teams WHERE account_id = ?)
                ) as total_members
            ', [$account->id])
            ->first();

        $trashedCount = $account->teams()->onlyTrashed()->count();

        $stats = [
            'total' => (int) ($aggregates->total ?? 0),
            'with_auto_assign' => (int) ($aggregates->with_auto_assign ?? 0),
            'without_auto_assign' => (int) ($aggregates->without_auto_assign ?? 0),
            'total_members' => (int) ($aggregates->total_members ?? 0),
            'trashed' => $trashedCount,
        ];

        return view('Chat::settings.teams.index', compact('teams', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::orderBy('firstname')->get();

        return view('Chat::settings.teams.create', compact('users'));
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

        return redirect()->route('settings.chat.teams.index')
            ->with('success', 'Equipo creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);

        $team->load([
            'members',
            'conversations' => function ($query) {
                $query->with(['customer', 'inbox', 'assignee'])
                    ->latest()
                    ->limit(10);
            },
        ]);

        return view('Chat::settings.teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $this->authorize('update', $team);

        $users = User::orderBy('firstname')->get();
        $team->load('members');

        return view('Chat::settings.teams.edit', compact('team', 'users'));
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

        return redirect()->route('settings.chat.teams.index')
            ->with('success', 'Equipo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('settings.chat.teams.index')
            ->with('success', 'Equipo eliminado exitosamente.');
    }

    /**
     * Restore a soft-deleted team.
     */
    public function restore($id)
    {
        $team = Team::withTrashed()->findOrFail($id);
        $this->authorize('restore', $team);

        $team->restore();

        return redirect()->route('settings.chat.teams.index')
            ->with('success', 'Equipo restaurado exitosamente.');
    }

    /**
     * Permanently delete a team.
     */
    public function forceDelete($id)
    {
        $team = Team::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $team);

        $name = $team->name;
        $team->forceDelete();

        return redirect()->route('settings.chat.teams.index')
            ->with('success', "Equipo '{$name}' eliminado permanentemente.");
    }
}
