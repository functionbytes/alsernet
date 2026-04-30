<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketViewRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketViewRequest;
use Modules\HelpdeskTickets\Models\TicketView;

class TicketViewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    /**
     * Display a listing of ticket views.
     */
    public function index(Request $request)
    {
        $query = TicketView::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $views = $query->ordered()->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => TicketView::count(),
            'system' => TicketView::where('is_system', true)->count(),
            'custom' => TicketView::where('is_system', false)->count(),
            'shared' => TicketView::where('is_shared', true)->count(),
        ];

        return view('theme.views.backups.helpdesk.ticket-views.index', [
            'views' => $views,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new view.
     */
    public function create()
    {
        return view('theme.views.backups.helpdesk.ticket-views.create');
    }

    /**
     * Store a newly created view.
     */
    public function store(StoreTicketViewRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();
        $validated['is_shared'] = $request->boolean('is_shared');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_system'] = false;
        $validated['ticket_id'] = null;

        TicketView::create($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', 'Vista creada exitosamente.');
    }

    /**
     * Show the form for editing a view.
     */
    public function edit(TicketView $view)
    {
        // Prevent editing system views
        if ($view->is_system) {
            return back()->with('error', 'No se pueden editar las vistas del sistema.');
        }

        return view('theme.views.backups.helpdesk.ticket-views.edit', compact('view'));
    }

    /**
     * Update the specified view.
     */
    public function update(UpdateTicketViewRequest $request, TicketView $view)
    {
        // Prevent editing system views
        if ($view->is_system) {
            return back()->with('error', 'No se pueden editar las vistas del sistema.');
        }

        $validated = $request->validated();

        $validated['is_shared'] = $request->boolean('is_shared');
        $validated['is_default'] = $request->boolean('is_default');

        $view->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', 'Vista actualizada exitosamente.');
    }

    /**
     * Remove the specified view.
     */
    public function destroy(TicketView $view)
    {
        // Prevent deleting system views
        if ($view->is_system) {
            return back()->with('error', 'No se pueden eliminar las vistas del sistema.');
        }

        $view->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', 'Vista eliminada exitosamente.');
    }

    /**
     * Reorder views via drag and drop.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:helpdesk_ticket_views,id',
        ]);

        TicketView::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
