<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionTicketStatusRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\ReorderTicketStatusRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketStatusRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketStatusRequest;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketStatusesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

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
    public function store(StoreTicketStatusRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_open'] = $request->boolean('is_open', true);
        $validated['is_default'] = $request->boolean('is_default');
        $validated['stops_sla_timer'] = $request->boolean('stops_sla_timer');

        TicketStatus::create($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-statuses.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.status.created'));
    }

    /**
     * Show the form for editing a status.
     */
    public function edit(TicketStatus $status)
    {
        return view('theme.views.backups.helpdesk.ticket-statuses.edit', [
            'status' => $status,
            // El panel lateral avisa de por que un estado no se puede borrar
            // antes de que el usuario lo intente: destroy() protege tanto el
            // estado por defecto como los que ya tienen tickets.
            'ticketsCount' => $status->tickets()->count(),
        ]);
    }

    /**
     * Update the specified status.
     */
    public function update(UpdateTicketStatusRequest $request, TicketStatus $status)
    {
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_open'] = $request->boolean('is_open');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['stops_sla_timer'] = $request->boolean('stops_sla_timer');

        $status->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-statuses.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.status.updated'));
    }

    /**
     * Remove the specified status.
     */
    public function destroy(TicketStatus $status)
    {
        if ($status->is_default) {
            return back()->with('error', __('helpdesktickets::helpdesktickets.settings.status.cannot_delete_default'));
        }

        // Check if status has tickets
        if ($status->tickets()->count() > 0) {
            return back()->with('error', __('helpdesktickets::helpdesktickets.settings.status.cannot_delete_with_tickets'));
        }

        $status->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-statuses.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.status.deleted'));
    }

    /**
     * Propone un slug unico a partir del nombre, para el boton "generar" de los
     * formularios. Sin esto el usuario solo descubre la colision al guardar, y
     * es facil chocar: Str::slug() colapsa los acentos, asi que "Soporte
     * Tecnico" y "Soporte Tecnico" (con tilde) producen el mismo slug.
     */
    public function ajaxSlug(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ignoreId' => ['nullable', 'integer'],
        ]);

        $base = Str::slug($validated['name']);

        if ($base === '') {
            return response()->json(['slug' => '']);
        }

        $taken = TicketStatus::query()
            ->when($validated['ignoreId'] ?? null, fn ($q, $id) => $q->whereKeyNot($id))
            ->where('slug', 'like', $base.'%')
            ->pluck('slug')
            ->map(fn ($slug) => strtolower($slug))
            ->all();

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken, true)) {
            $slug = $base.'-'.$suffix++;
        }

        return response()->json(['slug' => $slug]);
    }

    /**
     * Reorder statuses via drag and drop.
     */
    public function reorder(ReorderTicketStatusRequest $request)
    {
        $validated = $request->validated();

        TicketStatus::reorder($validated['ids']);

        return response()->json(['success' => true, 'message' => 'Orden actualizado exitosamente.']);
    }

    /**
     * Apply a bulk action (activate, deactivate or delete) to several statuses.
     */
    public function bulkAction(BulkActionTicketStatusRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;
        $skipped = 0;

        $statuses = TicketStatus::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            foreach ($statuses as $status) {
                if ($status->is_default || $status->tickets()->count() > 0) {
                    $skipped++;

                    continue;
                }

                $status->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($statuses as $status) {
                $status->update(['active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminado(s)', 'activate' => 'activado(s)', 'deactivate' => 'desactivado(s)'];
        $message = "{$count} estado(s) {$labels[$action]}.";
        if ($skipped > 0) {
            $message .= " {$skipped} omitido(s) por estar protegido(s).";
        }

        return response()->json(['message' => $message, 'count' => $count, 'skipped' => $skipped]);
    }
}
