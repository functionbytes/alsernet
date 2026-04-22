<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketStatusesController extends Controller
{
    /**
     * Display a listing of ticket statuses.
     */
    public function index(Request $request)
    {
        $query = TicketStatus::query();

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
            'total' => TicketStatus::count(),
            'open' => TicketStatus::where('is_open', true)->count(),
            'closed' => TicketStatus::where('is_open', false)->count(),
            'default' => TicketStatus::where('is_default', true)->count(),
        ];

        return view('theme.views.backups.helpdesk.ticket-statuses.index', [
            'statuses' => $statuses,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new status.
     */
    public function create()
    {
        return view('theme.views.backups.helpdesk.ticket-statuses.create');
    }

    /**
     * Store a newly created status.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:helpdesk_ticket_statuses,slug|regex:/^[a-z0-9_-]+$/',
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_open' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'stops_sla_timer' => 'nullable|boolean',
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_open'] = $request->boolean('is_open', true);
        $validated['is_default'] = $request->boolean('is_default');
        $validated['stops_sla_timer'] = $request->boolean('stops_sla_timer');

        TicketStatus::create($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-statuses.index')
            ->with('success', 'Estado creado exitosamente.');
    }

    /**
     * Show the form for editing a status.
     */
    public function edit(TicketStatus $status)
    {
        return view('theme.views.backups.helpdesk.ticket-statuses.edit', compact('status'));
    }

    /**
     * Update the specified status.
     */
    public function update(Request $request, TicketStatus $status)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('helpdesk_ticket_statuses', 'slug')->ignore($status->id),
            ],
            'color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_open' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'stops_sla_timer' => 'nullable|boolean',
        ], [
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB).',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_open'] = $request->boolean('is_open');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['stops_sla_timer'] = $request->boolean('stops_sla_timer');

        $status->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-statuses.index')
            ->with('success', 'Estado actualizado exitosamente.');
    }

    /**
     * Remove the specified status.
     */
    public function destroy(TicketStatus $status)
    {
        if ($status->is_default) {
            return back()->with('error', 'No se puede eliminar el estado predeterminado.');
        }

        // Check if status has tickets
        if ($status->tickets()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un estado que tiene tickets asociados.');
        }

        $status->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-statuses.index')
            ->with('success', 'Estado eliminado exitosamente.');
    }

    /**
     * Reorder statuses via drag and drop.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:helpdesk_ticket_statuses,id',
        ]);

        TicketStatus::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }
}
