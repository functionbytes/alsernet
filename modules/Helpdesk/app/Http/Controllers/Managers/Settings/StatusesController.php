<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Requests\Managers\Settings\ReorderStatusRequest;
use Modules\Helpdesk\Http\Requests\StoreConversationStatusRequest;
use Modules\Helpdesk\Http\Requests\UpdateConversationStatusRequest;
use Modules\Helpdesk\Models\ConversationStatus;

class StatusesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.statuses.view')->only(['index']);
        $this->middleware('can:helpdesk.statuses.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.statuses.update')->only(['edit', 'update', 'toggle', 'reorder']);
        $this->middleware('can:helpdesk.statuses.delete')->only(['destroy']);
    }

    /**
     * Display a listing of statuses.
     */
    public function index(Request $request)
    {
        $query = ConversationStatus::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $statuses = $query->ordered()->paginate(20);

        // Calculate statistics
        $row = ConversationStatus::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_open = 1 THEN 1 ELSE 0 END) as `open`,
            SUM(CASE WHEN is_closed = 1 THEN 1 ELSE 0 END) as closed,
            SUM(CASE WHEN is_default = 1 THEN 1 ELSE 0 END) as `default`
        ')->first();

        $stats = [
            'total' => (int) $row->total,
            'open' => (int) $row->open,
            'closed' => (int) $row->closed,
            'default' => (int) $row->default,
        ];

        return view('helpdesk::settings.statuses.index', [
            'statuses' => $statuses,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new status.
     */
    public function create()
    {
        return view('helpdesk::settings.statuses.create');
    }

    /**
     * Store a newly created status.
     */
    public function store(StoreConversationStatusRequest $request)
    {
        $validated = $request->validated();

        $isOpen = $request->boolean('is_open', true);

        $validated['is_open'] = $isOpen;
        $validated['is_closed'] = ! $isOpen;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['active'] = $request->boolean('active', true);
        $validated['is_system'] = false;

        ConversationStatus::create($validated);

        return redirect()->route('settings.helpdesk.statuses.index')
            ->with('success', 'Estado creado exitosamente.');
    }

    /**
     * Show the form for editing a status.
     */
    public function edit(ConversationStatus $status)
    {
        return view('helpdesk::settings.statuses.edit', compact('status'));
    }

    /**
     * Update the specified status.
     */
    public function update(UpdateConversationStatusRequest $request, ConversationStatus $status)
    {
        $validated = $request->validated();

        $isOpen = $request->boolean('is_open', true);

        $validated['is_open'] = $isOpen;
        $validated['is_closed'] = ! $isOpen;
        $validated['is_default'] = $request->boolean('is_default');
        $validated['active'] = $request->boolean('active');

        $status->update($validated);

        return redirect()->route('settings.helpdesk.statuses.index')
            ->with('success', 'Estado actualizado exitosamente.');
    }

    /**
     * Remove the specified status.
     */
    public function destroy(ConversationStatus $status)
    {
        if (! $status->canDelete()) {
            return back()->with('error', 'No se puede eliminar un estado del sistema.');
        }

        if ($status->is_default) {
            return back()->with('error', 'No se puede eliminar el estado predeterminado. Primero asigna otro estado como predeterminado.');
        }

        $status->delete();

        return redirect()->route('settings.helpdesk.statuses.index')
            ->with('success', 'Estado eliminado exitosamente.');
    }

    /**
     * Toggle the active state of a status (called by the route {status}/toggle).
     */
    public function toggle(ConversationStatus $status)
    {
        $status->update(['active' => ! $status->active]);

        return back()->with('success', 'Estado actualizado exitosamente.');
    }

    /**
     * Reorder statuses via drag and drop.
     */
    public function reorder(ReorderStatusRequest $request)
    {
        $validated = $request->validated();

        ConversationStatus::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
