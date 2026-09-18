<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionTicketViewRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\ReorderTicketViewRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketViewRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketViewRequest;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
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
        return view('theme.views.backups.helpdesk.ticket-views.create', $this->filterOptions());
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
            ->with('success', __('helpdesktickets::helpdesktickets.settings.view.created'));
    }

    /**
     * Show the form for editing a view.
     */
    public function edit(TicketView $view)
    {
        return view('theme.views.backups.helpdesk.ticket-views.edit', ['view' => $view] + $this->filterOptions());
    }

    /**
     * Datos para los selects del constructor de filtros (create/edit).
     */
    protected function filterOptions(): array
    {
        return [
            'statuses' => TicketStatus::ordered()->get(),
            'categories' => TicketCategory::active()->ordered()->get(),
            'groups' => TicketGroup::active()->ordered()->get(),
        ];
    }

    /**
     * Update the specified view.
     */
    public function update(UpdateTicketViewRequest $request, TicketView $view)
    {
        $validated = $request->validated();

        $validated['is_shared'] = $request->boolean('is_shared');
        $validated['is_default'] = $request->boolean('is_default');

        $view->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.view.updated'));
    }

    /**
     * Remove the specified view.
     */
    public function destroy(TicketView $view)
    {
        // Prevent deleting system views
        if ($view->is_system) {
            return back()->with('error', __('helpdesktickets::helpdesktickets.settings.view.cannot_delete_system'));
        }

        $view->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.view.deleted'));
    }

    /**
     * Reorder views via drag and drop.
     */
    public function reorder(ReorderTicketViewRequest $request)
    {
        $validated = $request->validated();

        TicketView::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }

    /**
     * Apply a bulk action (share, unshare or delete) to several views.
     */
    public function bulkAction(BulkActionTicketViewRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;
        $skipped = 0;

        $views = TicketView::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            foreach ($views as $view) {
                // Prevent deleting system views, same guard as destroy().
                if ($view->is_system) {
                    $skipped++;

                    continue;
                }

                $view->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($views as $view) {
                $view->update(['is_shared' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminada(s)', 'activate' => 'compartida(s)', 'deactivate' => 'dejada(s) de compartir'];
        $message = "{$count} vista(s) {$labels[$action]}.";
        if ($skipped > 0) {
            $message .= " {$skipped} omitida(s) por ser del sistema.";
        }

        return response()->json(['message' => $message, 'count' => $count, 'skipped' => $skipped]);
    }
}
