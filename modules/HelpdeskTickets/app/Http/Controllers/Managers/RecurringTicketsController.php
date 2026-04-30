<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreRecurringTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateRecurringTicketRequest;
use Modules\HelpdeskTickets\Models\Priority;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Modules\HelpdeskTickets\Models\TicketCategory;

class RecurringTicketsController extends Controller
{
    public function index(): View
    {
        $this->authorize('helpdesk.tickets.view');

        $recurringTickets = RecurringTicket::query()
            ->with(['category', 'priority', 'assignee'])
            ->orderBy('name')
            ->paginate(20);

        $stats = [
            'total' => RecurringTicket::query()->count(),
            'active' => RecurringTicket::query()->where('is_active', true)->count(),
            'inactive' => RecurringTicket::query()->where('is_active', false)->count(),
            'upcoming' => RecurringTicket::query()->where('is_active', true)->whereNotNull('next_run_at')->where('next_run_at', '>', now())->count(),
        ];

        return view('helpdesktickets::managers.recurring-tickets.index', [
            'recurringTickets' => $recurringTickets,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $this->authorize('helpdesk.tickets.create');

        return view('helpdesktickets::managers.recurring-tickets.form', [
            'recurringTicket' => null,
            'categories' => TicketCategory::active()->ordered()->get(),
            'priorities' => Priority::where('is_active', true)->orderBy('level')->get(),
            'agents' => User::select(['id', 'firstname', 'lastname'])->where('available', true)->where('verified', true)->orderBy('firstname')->get(),
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
            'agents' => User::select(['id', 'firstname', 'lastname'])->where('available', true)->where('verified', true)->orderBy('firstname')->get(),
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
