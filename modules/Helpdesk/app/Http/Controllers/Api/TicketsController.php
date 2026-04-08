<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;

class TicketsController extends Controller
{
    /**
     * List tickets with pagination and optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->with(['customer:id,name,email', 'status:id,name,color,slug', 'category:id,name,slug'])
            ->latest();

        if ($request->filled('status')) {
            $query->whereHas('status', fn ($q) => $q->where('slug', $request->status));
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->integer('assignee_id'));
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('ticket_number', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%");
            });
        }

        $tickets = $query->paginate($request->integer('per_page', 20));

        return response()->json($tickets);
    }

    /**
     * Create a new ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|integer|exists:helpdesk_ticket_categories,id',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'customer_id' => 'nullable|integer|exists:helpdesk_customers,id',
            'customer_email' => 'nullable|email',
            'customer_name' => 'nullable|string|max:255',
        ]);

        // Resolve or create customer
        if (! isset($validated['customer_id']) && isset($validated['customer_email'])) {
            $customer = Customer::firstOrCreate(
                ['email' => $validated['customer_email']],
                ['name' => $validated['customer_name'] ?? $validated['customer_email']]
            );
            $validated['customer_id'] = $customer->id;
        }

        $ticket = Ticket::create([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'priority' => $validated['priority'] ?? 'normal',
            'customer_id' => $validated['customer_id'] ?? null,
            'source' => 'api',
        ]);

        return response()->json($ticket->load(['customer:id,name,email', 'status:id,name,color', 'category:id,name']), 201);
    }

    /**
     * Get a single ticket by ticket number.
     */
    public function show(string $ticketNumber): JsonResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->with(['customer:id,name,email', 'status:id,name,color', 'category:id,name', 'assignee:id,name'])
            ->firstOrFail();

        return response()->json($ticket);
    }

    /**
     * Update a ticket by ticket number.
     */
    public function update(Request $request, string $ticketNumber): JsonResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status_id' => 'sometimes|integer|exists:helpdesk_ticket_statuses,id',
            'category_id' => 'sometimes|integer|exists:helpdesk_ticket_categories,id',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'assignee_id' => 'sometimes|nullable|integer|exists:users,id',
        ]);

        $ticket->update($validated);

        return response()->json($ticket->fresh(['customer:id,name,email', 'status:id,name,color', 'category:id,name', 'assignee:id,name']));
    }
}
