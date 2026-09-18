<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\TicketAssigned;
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
use Modules\HelpdeskTickets\Services\TicketAttachmentSecurityService;

class TicketMessagingController extends Controller
{
    public function __construct(
        private readonly MentionService $mentionService,
        private readonly TicketAttachmentSecurityService $attachmentSecurity,
    ) {}

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();
        $idempotencyKey = trim((string) ($request->header('X-Idempotency-Key') ?: $request->input('idempotency_key')));
        $idempotencyKey = $idempotencyKey !== '' ? Str::limit($idempotencyKey, 120, '') : null;

        // Escanear antes del lock y antes de escribir cualquier ruta evita
        // que una subida bloqueada deje basura persistida o mantenga la
        // transacción del ticket abierta durante el proceso de ClamAV.
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->attachmentSecurity->assertSafe($file);
            }
        }

        // La clave se guarda en metadata, que ya existe en TicketItem. El
        // lock de la fila Ticket cierra la carrera entre dos reintentos que
        // llegan exactamente al mismo tiempo, antes de almacenar archivos o
        // emitir broadcasts.
        $item = DB::transaction(function () use ($request, $ticket, $validated, $idempotencyKey) {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($idempotencyKey) {
                $existing = TicketItem::query()
                    ->where('ticket_id', $lockedTicket->id)
                    ->where(function ($query) use ($idempotencyKey): void {
                        // La columna indexada cubre las nuevas respuestas;
                        // el fallback conserva la deduplicación de mensajes
                        // creados durante el despliegue anterior que solo
                        // guardaba la clave en metadata.
                        $query->where('idempotency_key', $idempotencyKey)
                            ->orWhere('metadata->idempotency_key', $idempotencyKey);
                    })
                    ->first();

                if ($existing) {
                    return ['item' => $existing, 'replayed' => true];
                }
            }

            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachmentPaths[] = $file->store(
                        'helpdesk/tickets/'.$lockedTicket->id,
                        config('helpdesk.attachments.disk', 'local')
                    );
                }
            }

            return [
                'item' => $this->createMessageItem(
                    $lockedTicket,
                    $validated['body'],
                    $request->boolean('is_internal', false),
                    $attachmentPaths,
                    $idempotencyKey,
                ),
                'replayed' => false,
            ];
        });

        $itemWasAlreadyCreated = (bool) ($item['replayed'] ?? false);
        $item = $item['item'];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $itemWasAlreadyCreated
                    ? 'El mensaje ya había sido procesado.'
                    : __('helpdesktickets::helpdesktickets.messages.message_sent'),
                'item' => $item->load(['user']),
                // Enviar la respuesta puede haber asignado el ticket
                // (tickets.assign_on_reply) y/o cambiado su estado
                // (tickets.status_on_reply) del lado del servidor — el panel
                // "Asignado a" y la cabecera del detalle seguían mostrando el
                // dueño/estado de ANTES de responder hasta que el agente
                // recargaba a mano. fresh() para no arrastrar en memoria el
                // estado previo de $ticket, y el mismo toListRow() que ya usa
                // el resto de la pantalla para no inventar otra forma.
                'ticket' => $ticket->fresh()->toListRow(),
                'idempotent_replay' => (bool) $itemWasAlreadyCreated,
            ]);
        }

        // Rama que usaba el form clásico de la ficha completa (#reply-form,
        // sin interceptar por JS — show-full, eliminada el 8-sep-2026); el
        // panel superpuesto de /tickets responde JSON siempre (fetch), así
        // que ya no hay caller conocido, pero se deja el fallback.
        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
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
    private function createMessageItem(Ticket $ticket, string $body, bool $isInternal, array $attachmentPaths = [], ?string $idempotencyKey = null): TicketItem
    {
        $attributes = [
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => $body,
            'attachment_urls' => $attachmentPaths,
            'is_internal' => $isInternal,
        ];
        if ($idempotencyKey) {
            $attributes['idempotency_key'] = $idempotencyKey;
            // Se conserva metadata para que integraciones que ya inspeccionan
            // ese JSON sigan identificando respuestas reintentadas durante la
            // transición a la columna indexada.
            $attributes['metadata'] = ['idempotency_key' => $idempotencyKey];
        }
        $item = $ticket->items()->create($attributes);

        $data = ['last_message_at' => now()];
        // Solo una respuesta REAL al cliente cuenta como primera respuesta
        // — antes se marcaba con cualquier mensaje, notas internas
        // incluidas, así que un agente que solo dejaba una nota ("me lo
        // asigno, reviso en un rato") ya hacía desaparecer "Sin responder"
        // del listado aunque el cliente no hubiera recibido nada todavía
        // (bug real, encontrado en la revisión de código de la fila del
        // listado — QA 14-sep-2026, ver el chip "Sin responder" en
        // renderRow()/tickets-app/core.js).
        if (! $isInternal && ! $ticket->first_response_at) {
            $data['first_response_at'] = now();
        }
        $ticket->update($data);

        $this->mentionService->notifyMentions($body, $ticket);

        if (! $isInternal) {
            $this->applyAssignmentOnReply($ticket);
            $this->applyStatusOnReply($ticket);
        }

        // TicketMessageReceived/NewTicketMessage retirados de aquí
        // (14-sep-2026, auditoría de seguridad): TicketMessageReceived
        // broadcasteaba en new Channel('ticket.'.$ticket->id) -- PÚBLICO, sin
        // autenticar, a propósito para el widget del cliente -- pero se
        // disparaba SIEMPRE, incluida una nota interna ($isInternal true):
        // cualquiera suscrito a ese canal por websocket, sin login, recibía
        // el body/html_body completo de la nota. Ninguno de los dos eventos
        // tenía además listener ni consumidor real en el JS de este módulo
        // (confirmado por grep en todo el repo) -- MessageAdded::dispatch()
        // de arriba ya es la única fuente de verdad para "mensaje añadido en
        // vivo" (ver HelpdeskTicketsEventServiceProvider).
        MessageAdded::dispatch($item);

        return $item;
    }

    /**
     * Asignación automática al responder (ajuste tickets.assign_on_reply).
     *
     * Antes, un ticket sin dueño se podía contestar desde la bandeja sin que
     * nadie quedara como responsable: el agente escribía, el cliente recibía
     * respuesta, y el ticket seguía "Sin asignar" hasta que alguien lo tomara
     * a mano. Con esto, quien responde se convierte en el dueño.
     *
     * Solo si el ticket estaba SIN asignar: si ya tiene agente, responder no
     * se lo quita — dos compañeros contestando el mismo ticket no deben
     * robárselo el uno al otro sin querer. Va antes de applyStatusOnReply()
     * para que, si el ticket también pasa a Resuelto, el historial cuente
     * primero "se asignó" y después "se resolvió", en ese orden.
     */
    private function applyAssignmentOnReply(Ticket $ticket): void
    {
        if ($ticket->assignee_id) {
            return;
        }

        if (! Setting::get('tickets.assign_on_reply', true)) {
            return;
        }

        $agent = auth()->user();

        if (! $agent) {
            return;
        }

        $ticket->assignTo($agent->id);

        // Mismo evento que dispara el resto de vías de asignación
        // (AssignmentService, AutomationEngine, BulkTicketsController):
        // asignar sin él dejaba callados los listeners que avisan al agente
        // y las automatizaciones enganchadas a "ticket asignado".
        TicketAssigned::dispatch($ticket, $agent);
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
