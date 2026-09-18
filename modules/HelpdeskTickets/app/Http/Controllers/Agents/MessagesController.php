<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Http\Requests\Agents\StoreMessageRequest;
use Modules\HelpdeskTickets\Http\Requests\Agents\UpdateMessageRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;

class MessagesController extends Controller
{
    public function index(Ticket $ticket): JsonResponse
    {
        $messages = $ticket->messages()
            ->with(['user:id,firstname,lastname', 'author:id,name,email'])
            ->latest()
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(StoreMessageRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();

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

        // Mismo evento que el flujo de managers (TicketMessagingController):
        // sin él, las respuestas de agente no notificaban al cliente ni
        // registraban historial (listeners de MessageAdded).
        MessageAdded::dispatch($item);

        if ($request->expectsJson()) {
            // users no tiene columna `name` (es firstname/lastname + accessor
            // full_name en HasUserAttributes) — seleccionar `name` revienta.
            return response()->json($item->load('user:id,firstname,lastname'), 201);
        }

        return back()->with('success', __('helpdesk::helpdesk.messages.message_sent'));
    }

    public function update(UpdateMessageRequest $request, Ticket $ticket, TicketItem $message): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $message);

        $validated = $request->validated();

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
