<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketSearchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('manager.helpdesk.tickets.index');

        $query = Ticket::query()->with(['customer', 'category', 'status', 'assignee']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn ($b) => $b
                ->where('title', 'like', "%{$q}%")
                ->orWhere('subject', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->orWhere('ticket_number', 'like', "%{$q}%")
            );
        }

        foreach (['status_id', 'category_id', 'priority', 'assignee_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to.' 23:59:59');
        }

        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $results = $query->latest()->paginate(25)->appends($request->query());

        $agents = User::select(['id', 'firstname', 'lastname'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('firstname')
            ->get();

        return view('helpdesktickets::managers.search.index', [
            'results' => $results,
            'statuses' => TicketStatus::active()->ordered()->get(),
            'categories' => TicketCategory::active()->ordered()->get(),
            'agents' => $agents,
            'filters' => $request->only(['q', 'status_id', 'category_id', 'priority', 'assignee_id', 'from', 'to', 'tag']),
        ]);
    }
}
