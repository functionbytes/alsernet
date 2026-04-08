<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketGroup;

class TicketGroupsController extends Controller
{
    /**
     * Display a listing of ticket groups.
     */
    public function index(Request $request)
    {
        $query = TicketGroup::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $groups = $query->with('users')->ordered()->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => TicketGroup::count(),
            'active' => TicketGroup::where('is_active', true)->count(),
            'inactive' => TicketGroup::where('is_active', false)->count(),
            'default' => TicketGroup::where('is_default', true)->count(),
            'total_members' => \DB::connection('helpdesk')->table('helpdesk_ticket_group_user')->distinct('user_id')->count('user_id'),
        ];

        return view('theme.views.backups.helpdesk.ticket-groups.index', [
            'groups' => $groups,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $users = User::where('available', 1)
            ->where('confirmed', 1)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('theme.views.backups.helpdesk.ticket-groups.create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'assignment_mode' => 'required|in:manual,round_robin,load_balanced',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'user_priorities' => 'nullable|array',
            'user_priorities.*' => 'in:primary,backup',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        $group = TicketGroup::create($validated);

        // Attach users with priorities
        if ($request->filled('users')) {
            $usersData = [];
            foreach ($request->users as $index => $userId) {
                $usersData[$userId] = [
                    'priority' => $request->user_priorities[$index] ?? 'primary',
                ];
            }
            $group->users()->attach($usersData);
        }

        return redirect()->route('manager.helpdesk.backups.tickets.groups.index')
            ->with('success', 'Grupo creado exitosamente.');
    }

    /**
     * Show the form for editing a group.
     */
    public function edit(TicketGroup $group)
    {
        $group->load('users');
        $users = User::where('available', 1)
            ->where('confirmed', 1)
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('theme.views.backups.helpdesk.ticket-groups.edit', [
            'group' => $group,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, TicketGroup $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'assignment_mode' => 'required|in:manual,round_robin,load_balanced',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'user_priorities' => 'nullable|array',
            'user_priorities.*' => 'in:primary,backup',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        $group->update($validated);

        // Sync users with priorities
        if ($request->has('users')) {
            $usersData = [];
            if ($request->filled('users')) {
                foreach ($request->users as $index => $userId) {
                    $usersData[$userId] = [
                        'priority' => $request->user_priorities[$index] ?? 'primary',
                    ];
                }
            }
            $group->users()->sync($usersData);
        }

        return redirect()->route('manager.helpdesk.backups.tickets.groups.index')
            ->with('success', 'Grupo actualizado exitosamente.');
    }

    /**
     * Toggle the active status of a group.
     */
    public function toggle(TicketGroup $group)
    {
        $group->update(['is_active' => ! $group->is_active]);

        return back()->with('success', 'Estado del grupo actualizado exitosamente.');
    }

    /**
     * Remove the specified group.
     */
    public function destroy(TicketGroup $group)
    {
        // Check if group is default
        if ($group->is_default) {
            return back()->with('error', 'No se puede eliminar el grupo predeterminado.');
        }

        // Check if group has tickets assigned
        $ticketsCount = Ticket::where('group_id', $group->id)->count();
        if ($ticketsCount > 0) {
            return back()->with('error', 'No se puede eliminar un grupo que tiene tickets asignados.');
        }

        $group->delete();

        return redirect()->route('manager.helpdesk.backups.tickets.groups.index')
            ->with('success', 'Grupo eliminado exitosamente.');
    }

    /**
     * Reorder groups via drag and drop.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:helpdesk_ticket_groups,id',
        ]);

        TicketGroup::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
