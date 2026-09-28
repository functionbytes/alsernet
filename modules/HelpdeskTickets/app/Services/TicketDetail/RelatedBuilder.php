<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Illuminate\Support\Collection;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;

/**
 * Panel derecho "Relacionados": tickets enlazados a mano, resto del
 * histórico del mismo cliente y conversaciones paralelas. Extraído de
 * TicketDetailDataService (30-sep-2026) al trocear ese servicio por sección
 * de panel.
 */
class RelatedBuilder
{
    /**
     * Catálogo de estados, consultado como mucho UNA VEZ por petición (esta
     * clase vive tanto en relatedTicketsFor() como en customerTicketsFor(),
     * ambas llamadas desde la misma instancia inyectada en
     * TicketDetailDataService::build()). Antes cada método hacía su propio
     * with('...status')/with('linkedTicket.status')/with('ticket.status'):
     * perfilando /tickets/{id}/data (28-sep-2026, ticket 12184) el MISMO
     * "SELECT * FROM helpdesk_ticket_statuses WHERE id IN (1)" salía
     * repetido idéntico entre relatedTicketsFor() y customerTicketsFor() —
     * tickets del mismo cliente comparten pocos estados. No se reusa
     * CatalogCacheService::statuses() (cachea 1h) porque esa lista está
     * filtrada a estados ACTIVOS; un ticket histórico con un estado ya
     * desactivado se habría quedado sin nombre/slug.
     */
    private ?Collection $statusCatalog = null;

    private function statusCatalog(): Collection
    {
        return $this->statusCatalog ??= TicketStatus::query()->get()->keyBy('id');
    }

    /**
     * Adjunta la relación 'status' desde el catálogo memoizado en vez de
     * dejar que Eloquent la cargue (perezosa o vía with()) por su cuenta.
     */
    private function attachStatus(?Ticket $ticket): void
    {
        if ($ticket !== null && ! $ticket->relationLoaded('status')) {
            $ticket->setRelation('status', $this->statusCatalog()->get($ticket->status_id));
        }
    }

    /**
     * Otros tickets del mismo cliente — misma selección que
     * HelpdeskTicketBridgeService::getCustomerTickets() (Contactos 360 y la
     * bandeja de emails) sin su precarga de agente/categoría, excluyendo el
     * actual.
     */
    public function relatedTicketsFor(Ticket $ticket): array
    {
        // Dos fuentes distintas, fusionadas: los enlaces EXPLÍCITOS
        // (TicketLink, creados vía "Vincular ticket" en ambas direcciones —
        // se conserva su id/link_type propios para poder desvincular, cosa
        // que un ticket "relacionado" solo por ser del mismo cliente no
        // tiene) y los tickets del MISMO cliente (sugerencia automática).
        // unlinkTicket() solo borra filas donde ticket_id = $ticket->id, así
        // que solo el lado "propietario" del enlace (links(), no
        // linkedBy()) puede desvincularse desde aquí — desvincular desde el
        // otro extremo requeriría abrir el ticket contrario.
        // subticket_of va en su propia tarjeta (ver WorkBuilder).
        $ownLinks = $ticket->links()->where('link_type', '!=', 'subticket_of')->with('linkedTicket')->get()
            ->map(function ($l) {
                $this->attachStatus($l->linkedTicket);

                return ['link_id' => $l->id, 'link_type' => $l->link_type, 'ticket' => $l->linkedTicket, 'unlinkable' => true];
            });
        $reverseLinks = $ticket->linkedBy()->where('link_type', '!=', 'subticket_of')->with('ticket')->get()
            ->map(function ($l) {
                $this->attachStatus($l->ticket);

                return ['link_id' => $l->id, 'link_type' => $l->link_type, 'ticket' => $l->ticket, 'unlinkable' => false];
            });

        $explicitLinks = $ownLinks->concat($reverseLinks)->filter(fn ($row) => $row['ticket'] !== null);

        // Misma selección que HelpdeskTicketBridgeService::getCustomerTickets()
        // (últimos 6 del cliente), pero sin sus with(['status', 'category',
        // 'assignee']): esa precarga es para las vistas que pintan agente y
        // categoría por fila, y aquí solo se usan número, asunto y estado —
        // el estado sale del catálogo memoizado (attachStatus). Eran tres
        // consultas de más en cada apertura del detalle (QA 28-sep-2026).
        $customerRelated = $ticket->customer
            ? Ticket::query()
                ->where('customer_id', $ticket->customer_id)
                ->latest()
                ->limit(6)
                ->get()
                ->each(fn (Ticket $t) => $this->attachStatus($t))
                ->map(fn (Ticket $t) => ['link_id' => null, 'link_type' => null, 'ticket' => $t, 'unlinkable' => false])
            : collect();

        return $explicitLinks->concat($customerRelated)
            ->unique(fn ($row) => $row['ticket']->id)
            ->reject(fn ($row) => $row['ticket']->id === $ticket->id)
            ->take(8)
            ->map(fn ($row) => [
                'id' => $row['ticket']->id,
                'ticket_number' => $row['ticket']->ticket_number,
                'subject' => $row['ticket']->subject,
                'status_name' => $row['ticket']->status?->name,
                'status_slug' => $row['ticket']->statusSlug(),
                'link_type' => $row['link_type'],
                'url_unlink' => $row['unlinkable'] ? route('manager.helpdesk.tickets.unlink', [$ticket, $row['link_id']]) : null,
            ])->values()->all();
    }

    /**
     * "Conversación paralela" — mismo contrato que
     * TicketSideConversationsController::index(), resumido para el panel
     * lateral (Correo). No se duplica el endpoint completo: crear/añadir
     * mensaje siguen pasando por sus rutas propias.
     */
    public function sideConversationsFor(Ticket $ticket): array
    {
        return $ticket->sideConversations()
            ->with(['messages', 'participantUser:id,firstname,lastname'])
            ->get()
            ->map(fn ($side) => [
                'id' => $side->id,
                'subject' => $side->subject,
                'participant_type' => $side->participant_type,
                'participant_email' => $side->participant_email,
                'participant' => $side->participantUser?->full_name,
                'status' => $side->status,
                'message_count' => $side->messages->count(),
            ])->values()->all();
    }

    /**
     * Histórico de tickets del mismo cliente, excluyendo el que se está
     * mirando. Limitado a 20: es un panel lateral de contexto, no un
     * listado — quien necesite más tiene el filtro por cliente del listado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function customerTicketsFor(Ticket $ticket): array
    {
        if (! $ticket->customer_id) {
            return ['label' => null, 'counts' => ['total' => 0, 'open' => 0, 'resolved' => 0], 'items' => [], 'url_all' => null];
        }

        $base = fn () => Ticket::query()->where('customer_id', $ticket->customer_id);

        // Los contadores se calculan sobre TODOS los tickets del cliente, no
        // sobre los 20 que se listan: "14 totales" con 7 filas visibles es
        // justo lo que hace útil el pie "7 de 14".
        //
        // Los 3 count() por separado (antes: 3-4 queries reales — el
        // whereHas('status', ...) construye su propio EXISTS) se combinan
        // aquí en UNA sola query con agregación condicional. leftJoin en vez
        // de whereHas: el estado es un catálogo chico y esto es un COUNT,
        // no cambia qué filas se cuentan (mismo resultado, sin el EXISTS
        // repetido por fila). El scope de SoftDeletes de Ticket sigue
        // aplicando: leftJoin no lo desactiva.
        $row = $base()
            ->leftJoin('helpdesk_ticket_statuses', 'helpdesk_ticket_statuses.id', '=', 'helpdesk_tickets.status_id')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when helpdesk_ticket_statuses.is_open = 1 then 1 else 0 end) as open')
            ->selectRaw('sum(case when helpdesk_tickets.resolved_at is not null then 1 else 0 end) as resolved')
            ->first();

        $counts = [
            'total' => (int) $row->total,
            'open' => (int) $row->open,
            'resolved' => (int) $row->resolved,
        ];

        // El ticket abierto entra en la lista marcado como "Actual" en vez de
        // excluirse: verlo en su sitio dentro del historial del cliente ubica
        // mejor que una lista donde falta justo el que se está mirando.
        $items = $base()
            ->with(['assignee:id,email,firstname,lastname'])
            ->withCount('mails')
            ->latest()
            ->limit(20)
            ->get()
            ->each(fn (Ticket $other) => $this->attachStatus($other))
            ->map(fn (Ticket $other) => [
                'id' => $other->id,
                'ticket_number' => $other->ticket_number,
                'subject' => $other->subject ?? $other->title,
                'status_slug' => $other->statusSlug(),
                'status_name' => $other->status?->name,
                'priority' => $other->priority,
                'created_at_human' => $other->created_at?->diffForHumans(),
                'is_current' => $other->id === $ticket->id,
                'mails_count' => (int) $other->mails_count,
                // users no tiene columna `name`: el nombre se compone de
                // firstname/lastname, y con ambos vacíos queda el email.
                'agent_name' => $other->assignee
                    ? (trim(($other->assignee->firstname ?? '').' '.($other->assignee->lastname ?? '')) ?: $other->assignee->email)
                    : null,
                'url' => route('manager.helpdesk.tickets.index', ['ticket' => $other->id]),
            ])
            ->all();

        return [
            'label' => $ticket->customer?->company?->name ?? $ticket->customer?->name,
            'counts' => $counts,
            'items' => $items,
            'url_all' => route('manager.helpdesk.tickets.index', ['search' => $ticket->customer?->email]),
        ];
    }
}
