<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Helpdesk\Http\Requests\ReorderConversationViewsRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationViewRequest;
use Modules\Helpdesk\Http\Requests\UpdateConversationViewRequest;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\ConversationView;
use Modules\Helpdesk\Models\Group;

class ViewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.views.view')->only(['index', 'show']);
        $this->middleware('can:helpdesk.views.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.views.update')->only(['edit', 'update', 'reorder']);
        $this->middleware('can:helpdesk.views.delete')->only(['destroy']);
    }

    /**
     * Display a listing of views.
     */
    public function index(Request $request)
    {
        $baseQuery = ConversationView::query()->forUser(Auth::id());

        // Calculate stats — single aggregated query
        $statsRow = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as personal,
                SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public,
                SUM(CASE WHEN is_system = 1 THEN 1 ELSE 0 END) as system
            ', [Auth::id()])
            ->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'personal' => (int) $statsRow->personal,
            'public' => (int) $statsRow->public,
            'system' => (int) $statsRow->system,
        ];

        $query = clone $baseQuery;

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by scope (personal/public)
        if ($request->filled('scope')) {
            if ($request->scope === 'personal') {
                $query->where('user_id', Auth::id());
            } elseif ($request->scope === 'public') {
                $query->where('is_public', true);
            }
        }

        $views = $query->ordered()->paginate(20);

        return view('theme.views.backups.helpdesk.views.index', compact('views', 'stats'));
    }

    /**
     * Show the form for creating a new view.
     */
    public function create()
    {
        $statuses = ConversationStatus::active()->ordered()->get();
        $groups = Group::with('users')->get();

        return view('theme.views.backups.helpdesk.views.create', compact('statuses', 'groups'));
    }

    /**
     * Store a newly created view.
     */
    public function store(StoreConversationViewRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = Auth::id();
        $validated['is_public'] = $request->boolean('is_public');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_system'] = false;
        $validated['filters'] = $validated['filters'] ?? [];

        ConversationView::create($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', 'Vista creada exitosamente.');
    }

    /**
     * Show the form for editing a view.
     */
    public function edit(ConversationView $view)
    {
        // Check if user can edit this view
        if (! $view->canEdit(Auth::id())) {
            return redirect()->route('manager.helpdesk.settings.ticket-views.index')
                ->with('error', 'No tienes permiso para editar esta vista.');
        }

        $statuses = ConversationStatus::active()->ordered()->get();
        $groups = Group::with('users')->get();

        return view('theme.views.backups.helpdesk.views.edit', compact('view', 'statuses', 'groups'));
    }

    /**
     * Update the specified view.
     */
    public function update(UpdateConversationViewRequest $request, ConversationView $view)
    {
        // Check if user can edit this view
        if (! $view->canEdit(Auth::id())) {
            return back()->with('error', 'No tienes permiso para editar esta vista.');
        }

        $validated = $request->validated();

        $validated['is_public'] = $request->boolean('is_public');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['filters'] = $validated['filters'] ?? [];

        $view->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', 'Vista actualizada exitosamente.');
    }

    /**
     * Remove the specified view.
     */
    public function destroy(ConversationView $view)
    {
        // Check if user can delete this view
        if (! $view->canDelete()) {
            return back()->with('error', 'No se puede eliminar una vista del sistema.');
        }

        if (! $view->canEdit(Auth::id())) {
            return back()->with('error', 'No tienes permiso para eliminar esta vista.');
        }

        $view->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-views.index')
            ->with('success', 'Vista eliminada exitosamente.');
    }

    /**
     * Reorder views via drag and drop.
     */
    public function reorder(ReorderConversationViewsRequest $request)
    {
        $validated = $request->validated();

        ConversationView::reorder($validated['ids'], Auth::id());

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
