<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\HelpdeskTickets\Http\Requests\Managers\AddSideConversationMessageRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreSideConversationRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketSideConversation;
use Modules\HelpdeskTickets\Services\TicketSideConversationService;

/**
 * Side conversations de un ticket: hilos laterales privados con un compañero o
 * un contacto externo, invisibles para el cliente.
 */
class TicketSideConversationsController extends Controller
{
    public function __construct(private readonly TicketSideConversationService $service)
    {
        $this->middleware('can:helpdesk.tickets.view')->only('index');
        $this->middleware('can:helpdesk.tickets.update')->only(['store', 'addMessage', 'close']);
    }

    public function index(Ticket $ticket): JsonResponse
    {
        $sides = $ticket->sideConversations()
            ->with(['messages', 'participantUser:id,firstname,lastname'])
            ->get()
            ->map(fn (TicketSideConversation $side) => [
                'id' => $side->id,
                'subject' => $side->subject,
                'participant_type' => $side->participant_type,
                'participant_email' => $side->participant_email,
                'participant' => $side->participantUser?->full_name,
                'status' => $side->status,
                'messages' => $side->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'body' => $m->body,
                    'direction' => $m->direction,
                    'user_id' => $m->user_id,
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
            ]);

        return response()->json(['success' => true, 'data' => $sides]);
    }

    public function store(StoreSideConversationRequest $request, Ticket $ticket): JsonResponse
    {
        $side = $this->service->create($ticket, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Conversación lateral creada.',
            'data' => ['id' => $side->id],
        ], 201);
    }

    public function addMessage(AddSideConversationMessageRequest $request, Ticket $ticket, TicketSideConversation $sideConversation): JsonResponse
    {
        abort_unless($sideConversation->ticket_id === $ticket->id, 404);

        if (! $sideConversation->isOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'La conversación lateral está cerrada.',
            ], 409);
        }

        $message = $this->service->addMessage($sideConversation, $request->validated()['body'], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado.',
            'data' => ['id' => $message->id],
        ], 201);
    }

    public function close(Ticket $ticket, TicketSideConversation $sideConversation): JsonResponse
    {
        abort_unless($sideConversation->ticket_id === $ticket->id, 404);

        $this->service->close($sideConversation);

        return response()->json(['success' => true, 'message' => 'Conversación lateral cerrada.']);
    }
}
