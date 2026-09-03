<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreRecurringTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateRecurringTicketRequest;
use Modules\HelpdeskTickets\Models\Priority;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

class RecurringTicketsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('helpdesk.tickets.view');

        $query = RecurringTicket::query()->with(['category', 'priority', 'assignee']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%"));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->string('frequency'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        $recurringTickets = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total' => RecurringTicket::query()->count(),
            'active' => RecurringTicket::query()->where('is_active', true)->count(),
            'inactive' => RecurringTicket::query()->where('is_active', false)->count(),
            'upcoming' => RecurringTicket::query()->where('is_active', true)->whereNotNull('next_run_at')->where('next_run_at', '>', now())->count(),
        ];

        $categories = TicketCategory::active()->ordered()->get();

        return view('helpdesktickets::managers.recurring-tickets.index', [
            'recurringTickets' => $recurringTickets,
            'stats' => $stats,
            'categories' => $categories,
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $recurringTickets = RecurringTicket::query()->whereIn('id', $validated['ids'])->get();

        $count = 0;
        foreach ($recurringTickets as $recurringTicket) {
            if ($validated['action'] === 'delete') {
                if ($request->user()->cannot('delete', $recurringTicket)) {
                    continue;
                }
                $recurringTicket->delete();
                $count++;

                continue;
            }

            if ($request->user()->cannot('update', $recurringTicket)) {
                continue;
            }
            $recurringTicket->is_active = $validated['action'] === 'activate';
            $recurringTicket->save();
            $count++;
        }

        $verb = match ($validated['action']) {
            'activate' => 'activado(s)',
            'deactivate' => 'desactivado(s)',
            'delete' => 'eliminado(s)',
        };

        return response()->json([
            'success' => true,
            'message' => $count > 0
                ? "{$count} ticket(s) recurrente(s) {$verb}."
                : 'No se aplicó ningún cambio (sin permiso sobre los tickets recurrentes seleccionados).',
        ]);
    }

    public function create(): View
    {
        $this->authorize('helpdesk.tickets.create');

        return view('helpdesktickets::managers.recurring-tickets.form', [
            'recurringTicket' => null,
            'categories' => TicketCategory::active()->ordered()->get(),
            'priorities' => Priority::where('is_active', true)->orderBy('level')->get(),
            'agents' => CatalogCacheService::agents(),
        ]);
    }

    public function store(StoreRecurringTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        RecurringTicket::create($validated);

        return redirect()
            ->route('manager.helpdesk.recurring-tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.recurring_created'));
    }

    public function edit(RecurringTicket $recurringTicket): View
    {
        $this->authorize('helpdesk.tickets.update');

        return view('helpdesktickets::managers.recurring-tickets.form', [
            'recurringTicket' => $recurringTicket,
            'categories' => TicketCategory::active()->ordered()->get(),
            'priorities' => Priority::where('is_active', true)->orderBy('level')->get(),
            'agents' => CatalogCacheService::agents(),
        ]);
    }

    public function update(UpdateRecurringTicketRequest $request, RecurringTicket $recurringTicket): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $recurringTicket->update($validated);

        return redirect()
            ->route('manager.helpdesk.recurring-tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.recurring_updated'));
    }

    public function destroy(RecurringTicket $recurringTicket): RedirectResponse
    {
        $this->authorize('helpdesk.tickets.delete');

        $recurringTicket->delete();

        return redirect()
            ->route('manager.helpdesk.recurring-tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.recurring_deleted'));
    }

    /**
     * Toggle the active state of a recurring ticket schedule.
     */
    public function toggle(RecurringTicket $recurringTicket): RedirectResponse
    {
        $this->authorize('helpdesk.tickets.update');

        $recurringTicket->update(['is_active' => ! $recurringTicket->is_active]);

        return back()->with('success', __('helpdesk::helpdesk.messages.recurring_toggled'));
    }
}
