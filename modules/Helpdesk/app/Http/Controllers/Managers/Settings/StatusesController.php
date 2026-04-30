<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Requests\StoreConversationStatusRequest;
use Modules\Helpdesk\Models\ConversationStatus;

class StatusesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.statuses.view')->only(['index']);
        $this->middleware('can:helpdesk.statuses.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.statuses.update')->only(['edit', 'update', 'toggleActive', 'reorder']);
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
    public function update(Request $request, ConversationStatus $status)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('helpdesk_conversation_statuses', 'slug')->ignore($status->id),
            ],
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ]);

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
     * Toggle the active status.
     */
    public function toggleActive(ConversationStatus $status)
    {
        $status->update(['active' => ! $status->active]);

        return back()->with('success', 'Estado actualizado exitosamente.');
    }

    /**
     * Reorder statuses via drag and drop.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:helpdesk_conversation_statuses,id',
        ]);

        ConversationStatus::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
