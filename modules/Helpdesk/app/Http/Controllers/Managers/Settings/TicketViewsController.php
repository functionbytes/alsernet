<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\TicketView;

class TicketViewsController extends Controller
{
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
                    ->orWhere('slug', 'like', "%{$search}%")
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:helpdesk_ticket_views,slug|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string|max:1000',
            'icon' => 'fa-duotone nullable|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'conditions' => 'nullable|json',
            'is_shared' => 'nullable|boolean',
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['user_id'] = auth()->id();
        $validated['is_shared'] = $request->boolean('is_shared');
        $validated['is_system'] = false;

        TicketView::create($validated);

        return redirect()->route('manager.helpdesk.backups.tickets.views.index')
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
    public function update(Request $request, TicketView $view)
    {
        // Prevent editing system views
        if ($view->is_system) {
            return back()->with('error', 'No se pueden editar las vistas del sistema.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('helpdesk_ticket_views', 'slug')->ignore($view->id),
            ],
            'description' => 'nullable|string|max:1000',
            'icon' => 'fa-duotone nullable|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'conditions' => 'nullable|json',
            'is_shared' => 'nullable|boolean',
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_shared'] = $request->boolean('is_shared');

        $view->update($validated);

        return redirect()->route('manager.helpdesk.backups.tickets.views.index')
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

        return redirect()->route('manager.helpdesk.backups.tickets.views.index')
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
