<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionPriorityRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StorePriorityRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdatePriorityRequest;
use Modules\HelpdeskTickets\Models\Priority;

/**
 * Catalogo de prioridades (tabla helpdesk_priorities, ya usada por la API
 * publica api/v1/helpdesk/priorities). Alcance deliberadamente acotado a un
 * CRUD de referencia: hoy NO gobierna el campo helpdesk_tickets.priority
 * (string 'urgent'/'high'/'normal'/'low') ni los priority_multipliers del
 * motor de SLA, que siguen hardcodeados — ver [[project_helpdesktickets_settings_urls_fixed]].
 */
class TicketPrioritiesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    /**
     * Display a listing of priorities.
     */
    public function index(Request $request)
    {
        $query = Priority::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $priorities = $query->orderBy('level')->paginate(20);

        $stats = [
            'total' => Priority::count(),
            'active' => Priority::where('is_active', true)->count(),
            'inactive' => Priority::where('is_active', false)->count(),
        ];

        return view('theme.views.backups.helpdesk.ticket-priorities.index', [
            'priorities' => $priorities,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new priority.
     */
    public function create()
    {
        return view('theme.views.backups.helpdesk.ticket-priorities.create');
    }

    /**
     * Store a newly created priority.
     */
    public function store(StorePriorityRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Priority::create($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-priorities.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.priority.created'));
    }

    /**
     * Show the form for editing a priority.
     */
    public function edit(Priority $priority)
    {
        return view('theme.views.backups.helpdesk.ticket-priorities.edit', compact('priority'));
    }

    /**
     * Update the specified priority.
     */
    public function update(UpdatePriorityRequest $request, Priority $priority)
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $priority->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-priorities.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.priority.updated'));
    }

    /**
     * Remove the specified priority.
     */
    public function destroy(Priority $priority)
    {
        if (Priority::count() <= 1) {
            return back()->with('error', __('helpdesktickets::helpdesktickets.settings.priority.at_least_one'));
        }

        $priority->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-priorities.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.priority.deleted'));
    }

    /**
     * Apply a bulk action (activate, deactivate or delete) to several priorities.
     *
     * A borrado es todo-o-nada: como destroy() exige que quede al menos una
     * prioridad, si el lote a eliminar cubre el total existente se rechaza la
     * operacion completa en vez de borrar unas si y otras no.
     */
    public function bulkAction(BulkActionPriorityRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;

        $priorities = Priority::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            if ($priorities->count() >= Priority::count()) {
                return response()->json([
                    'message' => 'Debe quedar al menos una prioridad configurada.',
                ], 422);
            }

            foreach ($priorities as $priority) {
                $priority->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($priorities as $priority) {
                $priority->update(['is_active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminada(s)', 'activate' => 'activada(s)', 'deactivate' => 'desactivada(s)'];

        return response()->json([
            'message' => "{$count} prioridad(es) {$labels[$action]}.",
            'count' => $count,
        ]);
    }
}
