<?php

namespace Modules\Helpdesk\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketItem;

class MessagesController extends Controller
{
    public function index(Ticket $ticket): JsonResponse
    {
        $messages = $ticket->messages()
            ->with(['user:id,name', 'author:id,name,email'])
            ->latest()
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:65535',
            'is_internal' => 'boolean',
        ]);

        $item = $ticket->items()->create([
            'type' => 'message',
            'body' => strip_tags($validated['body']),
            'html_body' => $validated['body'],
            'is_internal' => $validated['is_internal'] ?? false,
            'user_id' => auth()->id(),
        ]);

        $ticket->update(['last_message_at' => now()]);

        // Set first_response_at if this is the first agent response
        if ($ticket->first_response_at === null) {
            $ticket->update(['first_response_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json($item->load('user:id,name'), 201);
        }

        return back()->with('success', __('helpdesk::helpdesk.messages.message_sent'));
    }

    public function update(Request $request, Ticket $ticket, TicketItem $message): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $message);

        $validated = $request->validate([
            'body' => 'required|string|max:65535',
        ]);

        $message->update([
            'body' => strip_tags($validated['body']),
            'html_body' => $validated['body'],
        ]);

        if ($request->expectsJson()) {
            return response()->json($message->fresh());
        }

        return back()->with('success', __('helpdesk::helpdesk.messages.message_updated'));
    }

    public function destroy(Ticket $ticket, TicketItem $message): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $message);

        $message->delete();

        if (request()->expectsJson()) {
            return response()->json(null, 204);
        }

        return back()->with('success', __('helpdesk::helpdesk.messages.message_deleted'));
    }
}
