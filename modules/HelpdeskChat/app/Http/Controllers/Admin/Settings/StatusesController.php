<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Conversations\ConversationStatus;

class StatusesController extends Controller
{
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
        $stats = [
            'total' => ConversationStatus::count(),
            'active' => ConversationStatus::where('active', true)->count(),
            'inactive' => ConversationStatus::where('active', false)->count(),
            'system' => ConversationStatus::where('is_system', true)->count(),
        ];

        return view('theme.views.settings.helpdesk.statuses.index', [
            'statuses' => $statuses,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new status.
     */
    public function create()
    {
        return view('theme.views.settings.helpdesk.statuses.create');
    }

    /**
     * Store a newly created status.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:helpdesk_conversation_statuses,slug|regex:/^[a-z0-9_-]+$/',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['active'] = $request->boolean('active', true);
        $validated['is_system'] = false;

        ConversationStatus::create($validated);

        return redirect()->route('manager.helpdesk.settings.tickets.statuses.index')
            ->with('success', 'Estado creado exitosamente.');
    }

    /**
     * Show the form for editing a status.
     */
    public function edit(ConversationStatus $status)
    {
        return view('theme.views.settings.helpdesk.statuses.edit', compact('status'));
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

        return redirect()->route('manager.helpdesk.settings.tickets.statuses.index')
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

        return redirect()->route('manager.helpdesk.settings.tickets.statuses.index')
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
