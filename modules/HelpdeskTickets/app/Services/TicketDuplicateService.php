<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Collection;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketLink;

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
    /**
     * TicketEmbeddingService NO se inyecta por constructor: arrastra a
     * EmbeddingService, cuyo $apiKey es `string` y explota con un TypeError
     * cuando no hay clave configurada (que es el caso por defecto). Como este
     * servicio se resuelve en la ruta /ai/duplicates, ese fallo convertía el
     * endpoint entero en un 500 aunque la similitud estuviese desactivada y no
     * hiciera falta ningún embedding. Se resuelve del contenedor solo cuando
     * de verdad se va a usar.
     */
    private function embeddings(): ?TicketEmbeddingService
    {
        try {
            return app(TicketEmbeddingService::class);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, array{ticket: Ticket, similarity: float, same_customer: bool}>
     */
    public function candidatesFor(Ticket $ticket, int $limit = 3): Collection
    {
        if (! config('helpdeskagents.ticket_similarity.enabled', false)) {
            return collect();
        }

        $embeddings = $this->embeddings();
        if ($embeddings === null || ! $embeddings->isAvailable()) {
            return collect();
        }

        $matches = $embeddings->similarTo($ticket, [
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

    /**
     * Candidatos por PARECIDO DE TEXTO, sin IA.
     *
     * candidatesFor() depende de embeddings, que en esta instalación están
     * desactivados (`ticket_similarity.enabled` = false): sin esto el aviso de
     * duplicado no se dispararía nunca. Aquí se compara el asunto carácter a
     * carácter, que es exactamente lo que describe el caso real —el cliente
     * reenvía el mismo email o repite la misma frase— y no necesita ningún
     * proveedor externo.
     *
     * Se restringe a tickets ABIERTOS del MISMO cliente dentro de la ventana
     * configurada: sin esas tres condiciones el parecido de asunto por sí solo
     * genera más ruido que aciertos ("Consulta", "Pedido", "Ayuda").
     *
     * @return Collection<int, array{ticket: Ticket, similarity: float, same_customer: bool}>
     */
    public function candidatesByText(string $subject, ?int $customerId, ?int $excludeTicketId = null, int $limit = 3): Collection
    {
        $subject = trim($subject);
        if ($subject === '' || $customerId === null) {
            return collect();
        }

        $days = (int) config('helpdeskagents.ticket_similarity.duplicate_window_days', 14);
        $threshold = (float) config('helpdeskagents.ticket_similarity.text_threshold', 0.75);

        // Ya enlazados a mano: alguien decidió que no eran duplicados (o que
        // sí, y los relacionó). Volver a avisar de los mismos dos tickets
        // convierte el aviso en algo que se ignora por costumbre. Mismo
        // criterio que candidatesFor().
        $linked = $excludeTicketId
            ? TicketLink::query()->where('ticket_id', $excludeTicketId)->pluck('linked_ticket_id')->all()
            : [];

        return Ticket::query()
            ->where('customer_id', $customerId)
            ->whereNull('closed_at')
            ->when($excludeTicketId, fn ($q) => $q->whereKeyNot($excludeTicketId))
            ->when($linked !== [], fn ($q) => $q->whereKeyNot($linked))
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['status:id,name'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Ticket $t): array => [
                'ticket' => $t,
                'similarity' => self::textSimilarity($subject, (string) $t->subject),
                'same_customer' => true,
            ])
            ->filter(fn (array $c) => $c['similarity'] >= $threshold)
            ->sortByDesc('similarity')
            ->take($limit)
            ->values();
    }

    /**
     * Parecido entre dos asuntos, de 0 a 1.
     *
     * similar_text() es sensible a mayúsculas, acentos y a los prefijos que
     * añaden los clientes de correo ("Re:", "Fwd:"): dos asuntos idénticos
     * salvo el "Re:" son el mismo asunto, y sin normalizar puntuarían bajo.
     */
    private static function textSimilarity(string $a, string $b): float
    {
        $normalize = static function (string $text): string {
            $text = mb_strtolower(trim($text));
            $text = (string) preg_replace('/^((re|rv|fwd|fw)\s*:\s*)+/iu', '', $text);
            $text = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

            return (string) preg_replace('/\s+/', ' ', $text);
        };

        $a = $normalize($a);
        $b = $normalize($b);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        // similar_text compara carácter a carácter, así que no reconoce el
        // mismo asunto escrito en otro orden ("Falta documentación del pedido
        // X" / "Pedido X sin documentación" da 44 %, y es el caso más común
        // cuando el cliente reescribe en vez de reenviar). El solapamiento de
        // palabras sí lo capta, así que se toma la mejor de las dos medidas.
        return round(max($percent / 100, self::tokenOverlap($a, $b)), 4);
    }

    /**
     * Palabras compartidas sobre el asunto más corto de los dos.
     *
     * Se ignoran las de tres letras o menos: "de", "el", "por" aparecen en
     * casi todos los asuntos y sin filtrarlas dos tickets sin relación
     * puntuarían alto.
     */
    private static function tokenOverlap(string $a, string $b): float
    {
        $words = static fn (string $t): array => array_values(array_unique(
            array_filter(preg_split('/[^a-z0-9]+/', $t) ?: [], fn ($w) => mb_strlen($w) > 3)
        ));

        $wa = $words($a);
        $wb = $words($b);

        if ($wa === [] || $wb === []) {
            return 0.0;
        }

        return count(array_intersect($wa, $wb)) / min(count($wa), count($wb));
    }
}
