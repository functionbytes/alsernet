<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\Managers\ScheduleReplyRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketScheduledReply;

/**
 * Respuestas programadas (send later): el agente redacta ahora y elige la hora
 * de envío; el comando ticket:send-scheduled-replies las entrega al vencer.
 * Puede listarlas y cancelarlas mientras sigan pendientes (sent_at null).
 */
class ScheduledRepliesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.update');
    }

    public function index(Ticket $ticket): JsonResponse
    {
        $pending = $ticket->scheduledReplies()
            ->whereNull('sent_at')
            ->orderBy('deliver_at')
            ->get()
            ->map(fn (TicketScheduledReply $reply) => [
                'id' => $reply->id,
                'body' => $reply->body,
                'is_internal' => $reply->is_internal,
                'deliver_at' => $reply->deliver_at?->toIso8601String(),
                'user_id' => $reply->user_id,
            ]);

        return response()->json(['success' => true, 'data' => $pending]);
    }

    public function store(ScheduleReplyRequest $request, Ticket $ticket): JsonResponse
    {
        $reply = $ticket->scheduledReplies()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
            'is_internal' => $request->boolean('is_internal'),
            'deliver_at' => $request->validated()['deliver_at'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta programada.',
            'data' => [
                'id' => $reply->id,
                'deliver_at' => $reply->deliver_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Ticket $ticket, TicketScheduledReply $scheduledReply): JsonResponse
    {
        abort_unless($scheduledReply->ticket_id === $ticket->id, 404);

        if ($scheduledReply->sent_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'La respuesta ya fue enviada y no puede cancelarse.',
            ], 409);
        }

        $scheduledReply->delete();

        return response()->json(['success' => true, 'message' => 'Respuesta programada cancelada.']);
    }
}
