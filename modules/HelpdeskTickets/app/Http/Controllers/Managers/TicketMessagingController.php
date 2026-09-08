<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\NewTicketMessage;
use Modules\HelpdeskTickets\Events\TicketMessageReceived;
use Modules\HelpdeskTickets\Events\TicketResolved;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Events\TicketTyping;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkReplyTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreTicketMessageRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\MentionService;

class TicketMessagingController extends Controller
{
    public function __construct(
        private readonly MentionService $mentionService,
    ) {}

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store(
                    'helpdesk/tickets/'.$ticket->id,
                    config('helpdesk.attachments.disk', 'local')
                );
            }
        }

        $item = $this->createMessageItem(
            $ticket,
            $validated['body'],
            $request->boolean('is_internal', false),
            $attachmentPaths
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.message_sent'),
                'item' => $item->load(['user']),
            ]);
        }

        // Rama solo usada por el form clásico de la ficha completa (#reply-form,
        // sin interceptar por JS) — el panel superpuesto de /tickets responde
        // JSON siempre (fetch), así que se queda en la ficha completa.
        return redirect()
            ->route('manager.helpdesk.tickets.show-full', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.message_sent'));
    }

    /**
     * Responde a varios tickets a la vez.
     *
     * Cada mensaje se crea por la misma vía que storeMessage (modelo + eventos
     * + menciones + broadcast) en lugar de un TicketItem::insert() crudo, y la
     * autorización se comprueba ticket a ticket con la policy: los tickets que
     * el usuario no puede actualizar se omiten y se reportan en la respuesta.
     */
    public function bulkReply(BulkReplyTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $validated = $request->validated();
        $isInternal = $request->boolean('is_internal');

        $tickets = Ticket::whereIn('id', $validated['ticket_ids'])->get();

        [$authorized, $skipped] = $tickets->partition(
            fn (Ticket $ticket) => $request->user()->can('update', $ticket)
        );

        if ($authorized->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para responder los tickets seleccionados.',
                'skipped_ticket_ids' => $skipped->pluck('id')->values(),
            ], 403);
        }

        DB::transaction(function () use ($authorized, $validated, $isInternal): void {
            foreach ($authorized as $ticket) {
                $this->createMessageItem($ticket, $validated['body'], $isInternal);
            }
        });

        $count = $authorized->count();

        return response()->json([
            'success' => true,
            'message' => "Respuesta enviada a {$count} ".($count === 1 ? 'ticket' : 'tickets').'.',
            'replied_ticket_ids' => $authorized->pluck('id')->values(),
            'skipped_ticket_ids' => $skipped->pluck('id')->values(),
        ]);
    }

    public function typing(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $user = $request->user();

        broadcast(new TicketTyping(
            ticketId: $ticket->id,
            userId: $user->id,
            userName: trim($user->firstname.' '.$user->lastname),
            isTyping: $request->boolean('is_typing', true),
        ))->toOthers();

        return response()->json(['ok' => true]);
    }

    /**
     * Vía única de creación de mensajes de agente: crea el TicketItem por el
     * modelo (observers incluidos), actualiza los timestamps del ticket,
     * notifica menciones y dispara los mismos eventos/broadcasts que una
     * respuesta individual.
     *
     * @param  array<int, string>  $attachmentPaths
     */
    private function createMessageItem(Ticket $ticket, string $body, bool $isInternal, array $attachmentPaths = []): TicketItem
    {
        $item = $ticket->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => $body,
            'attachment_urls' => $attachmentPaths,
            'is_internal' => $isInternal,
        ]);

        $data = ['last_message_at' => now()];
        if (! $ticket->first_response_at) {
            $data['first_response_at'] = now();
        }
        $ticket->update($data);

        $this->mentionService->notifyMentions($body, $ticket);

        if (! $isInternal) {
            $this->applyStatusOnReply($ticket);
        }

        MessageAdded::dispatch($item);

        broadcast(new TicketMessageReceived($ticket, $item));
        NewTicketMessage::dispatch($ticket, [
            'id' => $item->id,
            'content' => $item->body,
            'author' => auth()->user()?->fullName() ?: 'Agente',
            'created_at' => $item->created_at->toIso8601String(),
            'type' => 'agent',
        ]);

        return $item;
    }

    /**
     * Estado automático al responder al cliente (ajuste tickets.status_on_reply).
     *
     * El agente contestaba y el ticket seguía "Abierto" hasta que se acordaba
     * de marcarlo a mano, así que la bandeja acumulaba tickets ya atendidos.
     * Solo se aplica a respuestas VISIBLES: una nota interna no cierra nada.
     *
     * Se cambia el estado por la misma vía que el botón "Resolver"
     * (TicketLifecycleController): actualizar status_id a secas dejaría el
     * historial sin la entrada del cambio y sin disparar las automatizaciones
     * enganchadas a TicketStatusChanged.
     */
    private function applyStatusOnReply(Ticket $ticket): void
    {
        if (! Setting::get('tickets.status_on_reply', true)) {
            return;
        }

        $slug = (string) Setting::get('tickets.status_on_reply_slug', 'resolved');

        $destino = CatalogCacheService::statuses()->firstWhere('slug', $slug);

        if (! $destino || (int) $ticket->status_id === (int) $destino->id) {
            return;
        }

        $anterior = $ticket->status_id
            ? CatalogCacheService::statuses()->firstWhere('id', $ticket->status_id)
            : null;

        $datos = ['status_id' => $destino->id];

        // resolved_at es lo que miran los informes de resolución; Ticket::resolve()
        // lo fija y aquí no se puede llamar a ese método porque el estado destino
        // es configurable (puede ser "Esperando cliente").
        if ($slug === 'resolved' && ! $ticket->resolved_at) {
            $datos['resolved_at'] = now();
        }

        $ticket->update($datos);

        if ($anterior instanceof TicketStatus) {
            TicketStatusChanged::dispatch($ticket, $anterior, $destino);
        }

        if ($slug === 'resolved') {
            TicketResolved::dispatch($ticket);
        }
    }
}
