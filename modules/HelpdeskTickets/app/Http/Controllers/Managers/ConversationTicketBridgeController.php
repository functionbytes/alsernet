<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;

/**
 * Bridge endpoints called from the Helpdesk inbox UI to create / inspect
 * tickets tied to a conversation. Owned by HelpdeskTickets so Helpdesk
 * controllers do not import any HelpdeskTickets symbols.
 *
 * Routes are registered in HelpdeskTicketsServiceProvider but use the same
 * URLs and route names that the inbox JavaScript already calls, so no
 * frontend changes are required.
 */
class ConversationTicketBridgeController extends Controller
{
    public function create(Conversation $conversation, Request $request, TicketServiceContract $tickets): JsonResponse
    {
        $this->authorize('view', $conversation);

        if (! $tickets->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Módulo de tickets no disponible.',
            ], 422);
        }

        $payload = $request->only([
            'subject', 'description', 'priority', 'category_id', 'assignee_id',
        ]);

        $created = $tickets->createFromConversation($conversation, $payload);

        if (! $created) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el ticket.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Ticket #{$created['ticket_number']} creado correctamente.",
            'ticket_url' => $created['url'],
        ]);
    }

    public function show(Conversation $conversation, int $ticket, TicketServiceContract $tickets): JsonResponse
    {
        $this->authorize('view', $conversation);

        if (! $tickets->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Módulo de tickets no disponible.',
            ], 404);
        }

        $detail = $tickets->getTicketDetail($ticket);

        if (! $detail) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado.',
            ], 404);
        }

        $name = $detail['customer']['name'] ?? '—';
        $detail['customer']['initials'] = mb_strtoupper(
            collect(preg_split('/\s+/', trim($name)))
                ->take(2)
                ->map(fn ($w) => mb_substr($w, 0, 1))
                ->implode('')
        ) ?: '—';

        return response()->json([
            'success' => true,
            'ticket' => $detail,
        ]);
    }
}
