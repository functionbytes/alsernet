<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Services\EmbeddingService;
use Modules\HelpdeskAgents\Services\TicketEmbeddingService;

/**
 * Búsqueda de tickets por significado, no por literal.
 *
 * El buscador del módulo es `LIKE %texto%`: encuentra el ticket que contiene
 * exactamente las palabras tecleadas y ninguno más. Quien busca «el cliente que
 * no podía pagar con tarjeta» no encuentra el que dice «error al finalizar la
 * compra», aunque sean el mismo caso.
 *
 * Todo el trabajo caro ya estaba hecho: los vectores de los tickets se indexan
 * para la detección de duplicados e incidencias. Esto solo los consulta con
 * otra pregunta.
 *
 * COMPLEMENTA al buscador literal, no lo sustituye. El `LIKE` sigue siendo
 * mejor para lo que la gente busca la mitad de las veces —un número de ticket,
 * un número de pedido, un apellido— y ahí un vector no aporta nada. Por eso
 * esto devuelve ids para AMPLIAR los resultados, en vez de reemplazar la
 * consulta.
 */
class TicketSemanticSearchService
{
    /** Por debajo de esto la consulta es una palabra suelta: literal va mejor. */
    private const MIN_QUERY_LENGTH = 12;

    public function __construct(
        private readonly TicketEmbeddingService $tickets,
        private readonly EmbeddingService $embeddings,
    ) {}

    public function isAvailable(): bool
    {
        return config('helpdeskagents.ticket_similarity.enabled', false)
            && $this->tickets->isAvailable();
    }

    /**
     * Ids de tickets semánticamente cercanos a la consulta.
     *
     * @return array<int, int> ids, del más al menos parecido
     */
    public function search(string $query, int $limit = 25): array
    {
        $query = trim($query);

        if (! $this->isAvailable() || mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        // Un número de ticket o de pedido no se busca por significado: el
        // literal lo encuentra exacto y el vector solo añade ruido.
        if (preg_match('/^[\w\-\/#]+$/u', $query)) {
            return [];
        }

        try {
            $vector = $this->embeddings->embed($query);
        } catch (\Throwable $e) {
            Log::warning('TicketSemanticSearchService: no se pudo vectorizar la consulta', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return $this->tickets->similarToVector(
            $vector,
            $this->tickets->norm($vector),
            [
                'limit' => $limit,
                'days' => (int) config('helpdeskagents.ticket_similarity.search_window_days', 365),
                // Umbral más laxo que el de duplicados: aquí un resultado
                // relacionado pero no idéntico sigue siendo útil, mientras que
                // un falso duplicado manda al agente a mirar un ticket ajeno.
                'min_similarity' => (float) config('helpdeskagents.ticket_similarity.search_min_similarity', 0.72),
            ],
        )->pluck('ticket_id')->all();
    }
}
