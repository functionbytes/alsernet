<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskTickets\Models\Ticket;

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
        // Escalar es crear un ticket: se exige el mismo permiso que el alta
        // desde el panel (TicketsCrudController::store). Antes el único gate
        // era poder ver la conversación, así que cualquiera con acceso a la
        // bandeja creaba tickets aunque tuviera el permiso revocado.
        $this->authorize('create', Ticket::class);

        if (! $tickets->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Módulo de tickets no disponible.',
            ], 422);
        }

        // Sin validación, $request->only() iba directo a Ticket::create():
        // 'subject' desbordaba la columna, 'priority' aceptaba cualquier
        // cadena (el modal mandaba 'medium', que no existe en el módulo) y
        // los ids de categoría/agente no se comprobaban contra la BD.
        $payload = $request->validate([
            'subject' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:65535'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category_id' => ['nullable', 'integer', Rule::exists((new Ticket)->getConnectionName().'.helpdesk_ticket_categories', 'id')],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'group_id' => ['nullable', 'integer', Rule::exists((new Ticket)->getConnectionName().'.helpdesk_groups', 'id')],
            'attach_transcript' => ['nullable', 'boolean'],
            'notify_customer' => ['nullable', 'boolean'],
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
            'ticket' => $created,
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

        // El id del ticket llega de la URL y no se comprobaba contra la
        // conversación: con acceso a UNA conversación se podía leer el detalle
        // (asunto, descripción, nombre y email del cliente) de CUALQUIER
        // ticket iterando ids. El panel solo pinta tickets del mismo cliente,
        // así que se acepta cualquiera de los suyos, no solo los nacidos aquí.
        if (! $this->ticketBelongsToConversation($conversation, $ticket)) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado.',
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

    /**
     * Resolver o auto-asignarse el ticket desde el modal del inbox.
     *
     * El modal ya tenía un botón "Resolver ticket" desde su primera versión,
     * pero nunca hubo nada detrás: ningún handler en el JS y ningún endpoint
     * que aceptara la llamada. Los endpoints que sí existen viven en el CRUD de
     * agente y responden con back(), un 302 que desde AJAX no dice nada útil.
     * Este devuelve JSON y pasa por las MISMAS policies (resolve/assign), así
     * que no abre ningún atajo de permisos.
     */
    public function action(Conversation $conversation, int $ticket, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'action' => 'required|in:resolve,assign_me',
        ]);

        // Mismo cerrojo que show(): el id llega de la URL, y sin esto se podría
        // resolver CUALQUIER ticket teniendo acceso a una sola conversación.
        if (! $this->ticketBelongsToConversation($conversation, $ticket)) {
            return response()->json(['success' => false, 'message' => 'Ticket no encontrado.'], 404);
        }

        $model = Ticket::find($ticket);

        if (! $model) {
            return response()->json(['success' => false, 'message' => 'Ticket no encontrado.'], 404);
        }

        if ($validated['action'] === 'resolve') {
            $this->authorize('resolve', $model);
            $model->resolve();

            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.ticket_resolved'),
                'status' => $model->fresh()->status?->name,
            ]);
        }

        $this->authorize('assign', $model);
        $model->assignTo(auth()->id());

        return response()->json([
            'success' => true,
            'message' => __('helpdesk::helpdesk.messages.ticket_assigned'),
            'assignee' => trim((string) (auth()->user()?->firstname.' '.auth()->user()?->lastname)) ?: auth()->user()?->email,
        ]);
    }

    /**
     * Fragmento HTML del tab "Tickets" del panel derecho.
     *
     * Mismo patrón de carga perezosa que las pestañas Archivos / Anteriores /
     * Actividad del core (RightPanelTabController): el servidor devuelve la
     * vista ya renderizada y el JS solo la inyecta. Se añade porque tras
     * escalar una conversación el tab seguía diciendo "Sin tickets
     * relacionados" hasta recargar la bandeja entera.
     */
    public function ticketsTab(Conversation $conversation, TicketServiceContract $tickets): Response
    {
        $this->authorize('view', $conversation);

        $customer = $conversation->customer;

        $html = view('helpdesktickets::inbox-slots.right-panel-tickets-tab', [
            'rpTickets' => $customer ? $tickets->getCustomerTickets($customer, 5) : collect(),
            'rpConversationId' => $conversation->id,
        ])->render();

        return response($html);
    }

    /**
     * Un ticket es "de esta conversación" si nació de ella o si pertenece al
     * mismo cliente — que es exactamente lo que lista el tab Tickets del panel
     * derecho (TicketServiceContract::getCustomerTickets).
     */
    private function ticketBelongsToConversation(Conversation $conversation, int $ticketId): bool
    {
        return Ticket::query()
            ->whereKey($ticketId)
            ->where(function ($q) use ($conversation) {
                $q->where('conversation_id', $conversation->id);

                if ($conversation->customer_id) {
                    $q->orWhere('customer_id', $conversation->customer_id);
                }
            })
            ->exists();
    }
}
