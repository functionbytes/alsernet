<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketsController extends Controller
{
    public function index(Request $request): View
    {
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
        $categories = TicketCategory::active()->ordered()->get(['id', 'name']);
        $customers = Customer::orderBy('name')->get(['id', 'name', 'email']);

        return view('helpdesktickets::agents.tickets.create', compact('categories', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|integer|exists:helpdesk_ticket_categories,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'customer_id' => 'nullable|integer|exists:helpdesk_customers,id',
        ]);

        $ticket = Ticket::create(array_merge($validated, [
            'assignee_id' => auth()->id(),
            'source' => 'agent',
        ]));

        return redirect()->route('agent.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_created'));
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['customer', 'status', 'category', 'assignee', 'items.user', 'items.author']);

        $ticket->items()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $statuses = TicketStatus::active()->ordered()->get(['id', 'name', 'slug', 'color']);

        return view('helpdesktickets::agents.tickets.show', compact('ticket', 'statuses'));
    }

    public function edit(Ticket $ticket): View
    {
        $categories = TicketCategory::active()->ordered()->get(['id', 'name']);
        $statuses = TicketStatus::active()->ordered()->get(['id', 'name', 'color']);

        return view('helpdesktickets::agents.tickets.edit', compact('ticket', 'categories', 'statuses'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
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
        $ticket->delete();

        return redirect()->route('agent.helpdesk.tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.ticket_deleted'));
    }

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate(['agent_id' => 'required|integer|exists:users,id']);
        $ticket->assignTo($request->integer('agent_id'));

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_assigned'));
    }

    public function unassign(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['assignee_id' => null, 'assigned_at' => null]);

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_unassigned'));
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $ticket->close();

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_closed'));
    }

    public function reopen(Ticket $ticket): RedirectResponse
    {
        $ticket->reopen();

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_reopened'));
    }
}
