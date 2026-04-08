<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Priority;
use Modules\Helpdesk\Models\RecurringTicket;
use Modules\Helpdesk\Models\TicketCategory;

class RecurringTicketsController extends Controller
{
    public function index(): View
    {
        $this->authorize('manager.helpdesk.tickets.index');

        $recurringTickets = RecurringTicket::query()
            ->with(['category', 'priority', 'assignee'])
            ->orderBy('name')
            ->paginate(20);

        return view('helpdesk::managers.helpdesk.recurring-tickets.index', [
            'recurringTickets' => $recurringTickets,
        ]);
    }

    public function create(): View
    {
        $this->authorize('manager.helpdesk.tickets.create');

        return view('helpdesk::managers.helpdesk.recurring-tickets.form', [
            'recurringTicket' => null,
            'categories' => TicketCategory::active()->ordered()->get(),
            'priorities' => Priority::where('is_active', true)->orderBy('level')->get(),
            'agents' => User::select(['id', 'name'])->where('available', true)->where('verified', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.create');

        $validated = $this->validatedData($request);
        $validated['is_active'] = $request->boolean('is_active', true);

        RecurringTicket::create($validated);

        return redirect()
            ->route('recurring-tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.recurring_created'));
    }

    public function edit(RecurringTicket $recurringTicket): View
    {
        $this->authorize('manager.helpdesk.tickets.update');

        return view('helpdesk::managers.helpdesk.recurring-tickets.form', [
            'recurringTicket' => $recurringTicket,
            'categories' => TicketCategory::active()->ordered()->get(),
            'priorities' => Priority::where('is_active', true)->orderBy('level')->get(),
            'agents' => User::select(['id', 'name'])->where('available', true)->where('verified', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, RecurringTicket $recurringTicket): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.update');

        $validated = $this->validatedData($request);
        $validated['is_active'] = $request->boolean('is_active');

        $recurringTicket->update($validated);

        return redirect()
            ->route('recurring-tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.recurring_updated'));
    }

    public function destroy(RecurringTicket $recurringTicket): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.delete');

        $recurringTicket->delete();

        return redirect()
            ->route('recurring-tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.recurring_deleted'));
    }

    /**
     * Toggle the active state of a recurring ticket schedule.
     */
    public function toggle(RecurringTicket $recurringTicket): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.update');

        $recurringTicket->update(['is_active' => ! $recurringTicket->is_active]);

        return back()->with('success', __('helpdesk::helpdesk.messages.recurring_toggled'));
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:helpdesk_ticket_categories,id'],
            'priority_id' => ['nullable', 'exists:helpdesk_priorities,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'frequency' => ['required', 'in:daily,weekly,monthly,custom'],
            'cron_expression' => ['nullable', 'string', 'max:100'],
            'next_run_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
