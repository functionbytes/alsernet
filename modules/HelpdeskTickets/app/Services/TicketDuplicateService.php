<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Collection;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Candidatos a duplicado de un ticket.
 *
 * El caso real no es el cliente que abre dos tickets a propósito: es el que
 * reenvía el email original, el que responde fuera del hilo y genera uno nuevo,
 * y el que escribe por dos canales a la vez. Hoy eso se descubre a ojo, y con
 * suerte, después de que dos agentes hayan trabajado lo mismo por separado.
 *
 * La infraestructura para resolverlo ya estaba: `link_type` contempla
 * `duplicate_of` y `merge()` está completo con su historial. Lo único que
 * faltaba era el aviso.
 *
 * SUGIERE, no fusiona. Fusionar es destructivo (mueve mensajes y cierra un
 * ticket) y una similitud alta no es prueba: dos clientes con la misma avería
 * escriben casi igual sin que sus tickets sean el mismo.
 */
class TicketDuplicateService
{
    public function __construct(
        private readonly TicketEmbeddingService $embeddings,
    ) {}

    /**
     * @return Collection<int, array{ticket: Ticket, similarity: float, same_customer: bool}>
     */
    public function candidatesFor(Ticket $ticket, int $limit = 3): Collection
    {
        if (! config('helpdeskagents.ticket_similarity.enabled', false) || ! $this->embeddings->isAvailable()) {
            return collect();
        }

        $matches = $this->embeddings->similarTo($ticket, [
            'limit' => $limit * 3,
            'days' => (int) config('helpdeskagents.ticket_similarity.duplicate_window_days', 14),
        ]);

        if ($matches->isEmpty()) {
            return collect();
        }

        $tickets = Ticket::query()
            ->whereIn('id', $matches->pluck('ticket_id'))
            // Solo abiertos: un ticket cerrado hace tres semanas con el mismo
            // texto no es un duplicado, es un problema recurrente — y avisar de
            // él en cada ticket nuevo convierte el aviso en ruido.
            ->whereNull('closed_at')
            ->with(['status:id,name'])
            ->get()
            ->keyBy('id');

        // Ya enlazados a mano: no se vuelve a sugerir lo que alguien ya decidió.
        $linked = $ticket->links()->pluck('linked_ticket_id')->all();

        return $matches
            ->filter(fn (array $m) => $tickets->has($m['ticket_id']) && ! in_array($m['ticket_id'], $linked, true))
            ->map(fn (array $m): array => [
                'ticket' => $tickets->get($m['ticket_id']),
                'similarity' => $m['similarity'],
                // Mismo cliente eleva mucho la probabilidad de que sea el mismo
                // asunto; distinto cliente con texto casi idéntico apunta más a
                // una incidencia compartida que a un duplicado.
                'same_customer' => $tickets->get($m['ticket_id'])->customer_id === $ticket->customer_id,
            ])
            ->sortByDesc(fn (array $m) => [$m['same_customer'], $m['similarity']])
            ->take($limit)
            ->values();
    }
}
