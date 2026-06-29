<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Http\Requests\Api\StoreTicketApiRequest;
use Modules\HelpdeskTickets\Http\Requests\Api\UpdateTicketApiRequest;
use Modules\HelpdeskTickets\Http\Resources\TicketResource;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $tickets = Ticket::query()
            ->with(['customer:id,name,email', 'status:id,name,color,slug', 'category:id,name,slug'])
            ->when($request->filled('status'), fn ($q) => $q->whereHas('status', fn ($s) => $s->where('slug', $request->status)))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($s) => $s->where('slug', $request->category)))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('assignee_id'), fn ($q) => $q->where('assignee_id', $request->integer('assignee_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->search;
                $q->where(fn ($sub) => $sub->where('ticket_number', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ApiResponse::success(TicketResource::collection($tickets));
    }

    public function store(StoreTicketApiRequest $request): JsonResponse
    {
        $this->authorize('helpdesk.tickets.create');

        $validated = $request->validated();

        $ticket = DB::transaction(function () use ($validated) {
            if (! isset($validated['customer_id']) && isset($validated['customer_email'])) {
                $customer = Customer::firstOrCreate(
                    ['email' => $validated['customer_email']],
                    ['name' => $validated['customer_name'] ?? $validated['customer_email']]
                );
                $validated['customer_id'] = $customer->id;
            }

            return Ticket::create([
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'priority' => $validated['priority'] ?? 'normal',
                'customer_id' => $validated['customer_id'] ?? null,
                'source' => 'api',
            ]);
        });

        $ticket->load(['customer:id,name,email', 'status:id,name,color,slug', 'category:id,name,slug']);

        return ApiResponse::created(new TicketResource($ticket), 'Ticket creado correctamente.');
    }

    public function show(string $ticketNumber): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->with(['customer:id,name,email', 'status:id,name,color,slug', 'category:id,name,slug', 'assignee:id,firstname,lastname'])
            ->firstOrFail();

        return ApiResponse::success(new TicketResource($ticket));
    }

    public function update(UpdateTicketApiRequest $request, string $ticketNumber): JsonResponse
    {
        $this->authorize('helpdesk.tickets.update');

        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $ticket->update($request->validated());

        $ticket->load(['customer:id,name,email', 'status:id,name,color,slug', 'category:id,name,slug', 'assignee:id,firstname,lastname']);

        return ApiResponse::success(new TicketResource($ticket), 'Ticket actualizado correctamente.');
    }
}
