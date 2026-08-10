<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\NewTicketMessage;
use Modules\HelpdeskTickets\Events\TicketMessageReceived;
use Modules\HelpdeskTickets\Events\TicketTyping;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkReplyTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreTicketMessageRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
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

        MessageAdded::dispatch($item);

        broadcast(new TicketMessageReceived($ticket, $item));
        NewTicketMessage::dispatch($ticket, [
            'id' => $item->id,
            'content' => $item->body,
            'author' => auth()->user()->name,
            'created_at' => $item->created_at->toIso8601String(),
            'type' => 'agent',
        ]);

        return $item;
    }
}
