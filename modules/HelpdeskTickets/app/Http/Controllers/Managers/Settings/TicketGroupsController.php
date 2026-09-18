<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionTicketGroupRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\ReorderTicketGroupRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketGroupRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketGroupRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

class TicketGroupsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

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
            'default' => TicketGroup::where('default', true)->count(),
            // helpdesk_ticket_group_user es la tabla histórica que quedó
            // huérfana con la unificación de helpdesk_groups (ver
            // TicketGroup::users()/Modules\Helpdesk\Models\Group — ambos
            // módulos comparten helpdesk_group_user desde entonces); esta
            // consulta seguía apuntando a la tabla vieja y por eso siempre
            // daba 0 miembros, sin importar cuántos agentes tuviera un grupo.
            'total_members' => \DB::connection('helpdesk')->table('helpdesk_group_user')->distinct('user_id')->count('user_id'),
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
        return view('theme.views.backups.helpdesk.ticket-groups.create', [
            'users' => CatalogCacheService::agents(),
        ]);
    }

    /**
     * Store a newly created group.
     */
    public function store(StoreTicketGroupRequest $request)
    {
        $validated = $request->validated();

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        $group = TicketGroup::create($validated);

        // Attach users with priorities
        if ($request->filled('users')) {
            $usersData = [];
            foreach ($request->users as $index => $userId) {
                // conversation_priority: es como se llama la columna en el
                // pivot helpdesk_group_user, compartido con el reparto de
                // conversaciones. El formulario sigue enviando user_priorities.
                $usersData[$userId] = [
                    'conversation_priority' => $request->user_priorities[$index] ?? 'primary',
                ];
            }
            $group->users()->attach($usersData);
        }

        return redirect()->route('manager.helpdesk.settings.ticket-groups.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.group.created'));
    }

    /**
     * Show the form for editing a group.
     */
    public function edit(TicketGroup $group)
    {
        $group->load('users');

        return view('theme.views.backups.helpdesk.ticket-groups.edit', [
            'group' => $group,
            'users' => CatalogCacheService::agents(),
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(UpdateTicketGroupRequest $request, TicketGroup $group)
    {
        $validated = $request->validated();

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        $group->update($validated);

        // Sync users with priorities
        if ($request->has('users')) {
            $usersData = [];
            if ($request->filled('users')) {
                foreach ($request->users as $index => $userId) {
                    $usersData[$userId] = [
                        'conversation_priority' => $request->user_priorities[$index] ?? 'primary',
                    ];
                }
            }
            $group->users()->sync($usersData);
        }

        return redirect()->route('manager.helpdesk.settings.ticket-groups.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.group.updated'));
    }

    /**
     * Toggle the active status of a group.
     */
    public function toggle(TicketGroup $group)
    {
        $group->update(['is_active' => ! $group->is_active]);

        return back()->with('success', __('helpdesktickets::helpdesktickets.settings.group.toggled'));
    }

    /**
     * Remove the specified group.
     */
    public function destroy(TicketGroup $group)
    {
        // Check if group is default
        if ($group->is_default) {
            return back()->with('error', __('helpdesktickets::helpdesktickets.settings.group.cannot_delete_default'));
        }

        // Check if group has tickets assigned
        $ticketsCount = Ticket::where('group_id', $group->id)->count();
        if ($ticketsCount > 0) {
            return back()->with('error', __('helpdesktickets::helpdesktickets.settings.group.cannot_delete_with_tickets'));
        }

        $group->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-groups.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.group.deleted'));
    }

    /**
     * Reorder groups via drag and drop.
     */
    public function reorder(ReorderTicketGroupRequest $request)
    {
        $validated = $request->validated();

        TicketGroup::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }

    /**
     * Apply a bulk action (activate, deactivate or delete) to several groups.
     */
    public function bulkAction(BulkActionTicketGroupRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;
        $skipped = 0;

        $groups = TicketGroup::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            foreach ($groups as $group) {
                $isProtected = $group->is_default || Ticket::where('group_id', $group->id)->count() > 0;
                if ($isProtected) {
                    $skipped++;

                    continue;
                }

                $group->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($groups as $group) {
                $group->update(['is_active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminado(s)', 'activate' => 'activado(s)', 'deactivate' => 'desactivado(s)'];
        $message = "{$count} grupo(s) {$labels[$action]}.";
        if ($skipped > 0) {
            $message .= " {$skipped} omitido(s) por estar protegido(s).";
        }

        return response()->json(['message' => $message, 'count' => $count, 'skipped' => $skipped]);
    }
}
