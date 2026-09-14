<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Api\IndexSearchApiRequest;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;

class SearchController extends Controller
{
    public function index(IndexSearchApiRequest $request): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $q = $request->q;
        $type = $request->input('type', 'all');
        $results = [];

        if (in_array($type, ['tickets', 'all'])) {
            $results['tickets'] = Ticket::query()
                ->where(fn ($query) => $query->where('subject', 'like', "%{$q}%")
                    ->orWhere('ticket_number', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$q}%")))
                ->with(['status:id,name,color', 'customer:id,name'])
                ->limit(10)
                ->get(['id', 'ticket_number', 'subject', 'status_id', 'customer_id', 'created_at'])
                ->map(fn ($ticket) => [
                    'id' => $ticket->id,
                    'ticketNumber' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status ? ['id' => $ticket->status->id, 'name' => $ticket->status->name, 'color' => $ticket->status->color] : null,
                    'customer' => $ticket->customer ? ['id' => $ticket->customer->id, 'name' => $ticket->customer->name] : null,
                    'createdAt' => $ticket->created_at->toIso8601String(),
                ]);
        }

        if (in_array($type, ['customers', 'all'])) {
            $results['customers'] = Customer::query()
                ->where(fn ($query) => $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%"))
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone]);
        }

        if (in_array($type, ['conversations', 'all'])) {
            $results['conversations'] = Conversation::query()
                ->where(fn ($query) => $query->where('subject', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$q}%")))
                ->with(['status:id,name', 'customer:id,name'])
                ->limit(10)
                ->get(['id', 'subject', 'status_id', 'customer_id', 'created_at'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'subject' => $c->subject,
                    'status' => $c->status ? ['id' => $c->status->id, 'name' => $c->status->name] : null,
                    'customer' => $c->customer ? ['id' => $c->customer->id, 'name' => $c->customer->name] : null,
                    'createdAt' => $c->created_at->toIso8601String(),
                ]);
        }

        return ApiResponse::success($results);
    }
}
