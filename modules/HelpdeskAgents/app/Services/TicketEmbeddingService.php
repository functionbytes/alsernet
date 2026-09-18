<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\VectorMath;
use Modules\HelpdeskAgents\Models\TicketEmbedding;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Indexación y búsqueda por similitud del texto de los tickets.
 *
 * Base común de dos funciones que parecen distintas y comparten todo el
 * mecanismo: encontrar el duplicado de un ticket, y detectar que veinte
 * tickets de la última hora hablan de lo mismo.
 *
 * El coseno se calcula en PHP, igual que en KnowledgeRetrievalService y por el
 * mismo motivo: el deploy fija mariadb:11 (sin VEC_DISTANCE_COSINE, que llega
 * en 11.7) y el vector se guarda como JSON, no como columna VECTOR. Para que
 * ese cálculo tenga techo, la búsqueda va SIEMPRE acotada por ventana temporal
 * y por candidatos máximos — no se recorre el histórico entero.
 */
class TicketEmbeddingService
{
    public function __construct(
        private readonly EmbeddingService $embeddings,
    ) {}

    public function isAvailable(): bool
    {
        return $this->embeddings->isConfigured();
    }

    /**
     * Genera y guarda el vector de un ticket. Idempotente: reindexar
     * sobrescribe la fila en vez de acumular versiones.
     */
    public function index(Ticket $ticket): ?TicketEmbedding
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $text = $this->textFor($ticket);

        if ($text === '') {
            return null;
        }

        try {
            $vector = $this->embeddings->embed($text);
        } catch (\Throwable $e) {
            Log::warning('TicketEmbeddingService: no se pudo generar el vector', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return TicketEmbedding::query()->updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'embedding' => $vector,
                'vector_norm' => $this->norm($vector),
                'embedding_model' => (string) config('helpdeskagents.embeddings.model', 'text-embedding-3-small'),
                'category_id' => $ticket->category_id,
                'customer_id' => $ticket->customer_id,
            ]
        );
    }

    /**
     * Tickets parecidos al dado, de más a menos.
     *
     * @param  array{days?: int, category_id?: int|null, customer_id?: int|null, limit?: int, min_similarity?: float, exclude?: array<int, int>}  $options
     * @return Collection<int, array{ticket_id: int, similarity: float}>
     */
    public function similarTo(Ticket $ticket, array $options = []): Collection
    {
        $source = TicketEmbedding::query()->where('ticket_id', $ticket->id)->first();

        if (! $source) {
            return collect();
        }

        return $this->similarToVector($source->embedding, $source->vector_norm, array_merge(
            ['exclude' => [$ticket->id]],
            $options,
        ));
    }

    /**
     * @param  array<int, float>  $vector
     * @param  array{days?: int, category_id?: int|null, customer_id?: int|null, limit?: int, min_similarity?: float, exclude?: array<int, int>}  $options
     * @return Collection<int, array{ticket_id: int, similarity: float}>
     */
    public function similarToVector(array $vector, float $norm, array $options = []): Collection
    {
        if ($norm <= 0.0) {
            return collect();
        }

        $days = (int) ($options['days'] ?? config('helpdeskagents.ticket_similarity.window_days', 30));
        $limit = (int) ($options['limit'] ?? 5);
        $minSimilarity = (float) ($options['min_similarity']
            ?? config('helpdeskagents.ticket_similarity.min_similarity', 0.86));
        $maxCandidates = max(1, (int) config('helpdeskagents.ticket_similarity.max_candidates', 1500));
        $exclude = $options['exclude'] ?? [];

        // Solo se comparan vectores del MISMO modelo: dos modelos distintos
        // producen espacios incomparables y su coseno no significa nada.
        $model = (string) config('helpdeskagents.embeddings.model', 'text-embedding-3-small');

        $candidates = TicketEmbedding::query()
            ->where('embedding_model', $model)
            ->where('created_at', '>=', now()->subDays($days))
            ->when($exclude !== [], fn ($q) => $q->whereNotIn('ticket_id', $exclude))
            ->when(
                array_key_exists('category_id', $options) && $options['category_id'] !== null,
                fn ($q) => $q->where('category_id', $options['category_id'])
            )
            ->when(
                array_key_exists('customer_id', $options) && $options['customer_id'] !== null,
                fn ($q) => $q->where('customer_id', $options['customer_id'])
            )
            ->orderByDesc('id')
            ->limit($maxCandidates)
            ->select(['ticket_id', 'embedding', 'vector_norm'])
            ->cursor();

        $scores = [];

        foreach ($candidates as $candidate) {
            $similarity = $this->cosine($vector, $norm, $candidate->embedding, $candidate->vector_norm);

            if ($similarity >= $minSimilarity) {
                $scores[$candidate->ticket_id] = $similarity;
            }
        }

        arsort($scores);

        return collect(array_slice($scores, 0, $limit, true))
            ->map(fn (float $similarity, int $ticketId): array => [
                'ticket_id' => $ticketId,
                'similarity' => round($similarity, 4),
            ])
            ->values();
    }

    /**
     * Vectores de los tickets creados en una ventana reciente — la entrada del
     * agrupamiento de incidencias.
     *
     * @return Collection<int, TicketEmbedding>
     */
    public function recent(int $minutes, int $limit = 300): Collection
    {
        $model = (string) config('helpdeskagents.embeddings.model', 'text-embedding-3-small');

        return TicketEmbedding::query()
            ->where('embedding_model', $model)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['ticket_id', 'embedding', 'vector_norm', 'category_id', 'created_at']);
    }

    /**
     * Texto que representa al ticket: asunto y primer mensaje del cliente.
     *
     * Deliberadamente NO incluye las respuestas del agente. Dos clientes con el
     * mismo problema escriben parecido; las respuestas del equipo, en cambio,
     * usan siempre las mismas plantillas, así que meterlas haría que todos los
     * tickets se parecieran entre sí y el umbral dejara de discriminar.
     */
    public function textFor(Ticket $ticket): string
    {
        $first = $ticket->items()
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->oldest('created_at')
            ->value('body');

        $body = trim(strip_tags((string) ($first ?: $ticket->description)));
        $subject = trim((string) $ticket->subject);

        return trim(mb_substr($subject."\n".$body, 0, 4000));
    }

    /**
     * Fachada sobre VectorMath (Core), donde vive el cálculo compartido con la
     * base de conocimiento y el centro de ayuda. Se conserva como método del
     * servicio porque las llamadas pasan la norma ya guardada en base de datos
     * y ese orden de argumentos es el que usan los llamadores.
     *
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public function cosine(array $a, float $normA, array $b, ?float $normB): float
    {
        return VectorMath::cosine($a, $b, $normA, $normB);
    }

    /**
     * @param  array<int, float>  $vector
     */
    public function norm(array $vector): float
    {
        return VectorMath::norm($vector);
    }
}
