<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketFollowupRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketFollowup;

/**
 * Recordatorios de seguimiento de un ticket: el agente programa "revisar este
 * ticket el día X"; un comando programado (ticket:send-followups) los notifica
 * al vencer. Hasta ahora la tabla/modelo existían sin punto de entrada.
 */
class TicketFollowupsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.update');
    }

    public function store(StoreTicketFollowupRequest $request, Ticket $ticket): JsonResponse
    {
        $followup = $ticket->followups()->create([
            'user_id' => $request->user()->id,
            'scheduled_at' => $request->validated()['scheduled_at'],
            'note' => $request->validated()['note'] ?? null,
            'is_sent' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seguimiento programado.',
            'data' => [
                'id' => $followup->id,
                'scheduled_at' => $followup->scheduled_at?->toIso8601String(),
                'note' => $followup->note,
            ],
        ], 201);
    }

    public function destroy(Ticket $ticket, TicketFollowup $followup): JsonResponse
    {
        abort_unless($followup->ticket_id === $ticket->id, 404);

        $followup->delete();

        return response()->json(['success' => true, 'message' => 'Seguimiento cancelado.']);
    }
}
