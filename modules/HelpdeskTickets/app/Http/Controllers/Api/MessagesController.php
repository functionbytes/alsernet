<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\HelpdeskTickets\Http\Requests\Api\StoreMessageApiRequest;
use Modules\HelpdeskTickets\Http\Resources\MessageResource;
use Modules\HelpdeskTickets\Models\Ticket;

class MessagesController extends Controller
{
    public function index(Request $request, string $ticketNumber): JsonResponse
    {
        $this->authorize('helpdesk.tickets.view');

        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $messages = $ticket->items()
            ->with(['user:id,name'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ApiResponse::success(MessageResource::collection($messages));
    }

    public function store(StoreMessageApiRequest $request, string $ticketNumber): JsonResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><blockquote><pre><code><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td><span><div>';

        $item = DB::transaction(function () use ($request, $ticket, $allowedTags) {
            $item = $ticket->items()->create([
                'type' => 'message',
                'body' => strip_tags($request->body),
                'html_body' => strip_tags($request->body, $allowedTags),
                'is_internal' => $request->boolean('is_internal'),
                'user_id' => auth()->id(),
            ]);

            $ticket->update(['last_message_at' => now()]);

            return $item;
        });

        $item->load('user:id,name');

        return ApiResponse::created(new MessageResource($item), 'Mensaje enviado correctamente.');
    }
}
