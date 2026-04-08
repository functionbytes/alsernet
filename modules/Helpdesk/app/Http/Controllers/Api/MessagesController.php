<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Ticket;

class MessagesController extends Controller
{
    /**
     * List messages for a ticket.
     */
    public function index(string $ticketNumber): JsonResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $messages = $ticket->messages()
            ->with(['user:id,name', 'author:id,name,email'])
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * Add a message to a ticket.
     */
    public function store(Request $request, string $ticketNumber): JsonResponse
    {
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        $validated = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><blockquote><pre><code><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td><span><div>';

        $item = $ticket->items()->create([
            'type' => 'message',
            'body' => strip_tags($validated['body']),
            'html_body' => strip_tags($validated['body'], $allowedTags),
            'is_internal' => $validated['is_internal'] ?? false,
            'user_id' => auth()->id(),
        ]);

        $ticket->update(['last_message_at' => now()]);

        return response()->json($item->load('user:id,name'), 201);
    }
}
