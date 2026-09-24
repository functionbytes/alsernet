<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketDraft;

/**
 * Borrador del composer en servidor. Solo el propio agente lee y escribe el
 * suyo; lo que ven los demás ("X tiene un borrador") sale sin el texto en
 * TicketDetailDataController::data().
 */
class TicketDraftsController extends Controller
{
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:20000'],
            'mode' => ['nullable', 'in:reply,note'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));

        if ($body === '') {
            TicketDraft::query()->where('ticket_id', $ticket->id)->where('user_id', $request->user()->id)->delete();

            return response()->json(['saved' => false]);
        }

        TicketDraft::query()->updateOrCreate(
            ['ticket_id' => $ticket->id, 'user_id' => $request->user()->id],
            ['body' => $validated['body'], 'mode' => $validated['mode'] ?? 'reply'],
        );

        return response()->json(['saved' => true]);
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        TicketDraft::query()->where('ticket_id', $ticket->id)->where('user_id', $request->user()->id)->delete();

        return response()->json(['saved' => false]);
    }
}
