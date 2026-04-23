<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $userId = auth()->id();

        $query = Ticket::query()
            ->with(['customer:id,name,email', 'status:id,name,color', 'category:id,name'])
            ->where('assignee_id', $userId)
            ->latest();

        if ($request->filled('status')) {
            $query->whereHas('status', fn ($q) => $q->where('slug', $request->status));
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(fn ($q) => $q
                ->where('ticket_number', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%")
            );
        }

        $tickets = $query->paginate(20)->appends($request->query());
        $statuses = TicketStatus::active()->ordered()->get(['id', 'name', 'slug', 'color']);

        return view('helpdesktickets::agents.tickets.index', compact('tickets', 'statuses'));
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        $categories = TicketCategory::active()->ordered()->get(['id', 'name']);
        $customers = Customer::orderBy('name')->get(['id', 'name', 'email']);

        return view('helpdesktickets::agents.tickets.create', compact('categories', 'customers'));
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validated();

        $ticket = Ticket::create(array_merge($validated, [
            'assignee_id' => auth()->id(),
            'source' => 'agent',
        ]));

        return redirect()->route('agent.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_created'));
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['customer', 'status', 'category', 'assignee', 'items.user', 'items.author']);

        $ticket->items()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $statuses = TicketStatus::active()->ordered()->get(['id', 'name', 'slug', 'color']);

        return view('helpdesktickets::agents.tickets.show', compact('ticket', 'statuses'));
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);

        $categories = TicketCategory::active()->ordered()->get(['id', 'name']);
        $statuses = TicketStatus::active()->ordered()->get(['id', 'name', 'color']);

        return view('helpdesktickets::agents.tickets.edit', compact('ticket', 'categories', 'statuses'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status_id' => 'sometimes|integer|exists:helpdesk_ticket_statuses,id',
            'category_id' => 'sometimes|integer|exists:helpdesk_ticket_categories,id',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
        ]);

        $ticket->update($validated);

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_updated'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return redirect()->route('agent.helpdesk.tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.ticket_deleted'));
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $request->validate(['agent_id' => 'required|integer|exists:users,id']);
        $ticket->assignTo($request->integer('agent_id'));

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_assigned'));
    }

    public function unassign(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update(['assignee_id' => null, 'assigned_at' => null]);

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_unassigned'));
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $this->authorize('close', $ticket);

        $ticket->close();

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_closed'));
    }

    public function reopen(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $ticket->reopen();

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_reopened'));
    }
}
