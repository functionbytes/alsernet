<?php

namespace Modules\HelpdeskAgents\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Models\AiAgentKnowledgeBase;

class KnowledgeRetrievalService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService
    ) {}

    public function findRelevant(AiAgent $agent, string $query, ?int $topK = null): Collection
    {
        $topK ??= config('helpdeskagents.embeddings.top_k', 5);

        if (! $this->embeddingService->isConfigured()) {
            return $this->findByFulltext($agent, $query, $topK);
        }

        try {
            return $this->findBySimilarity($agent, $query, $topK, config('helpdeskagents.embeddings.min_similarity', 0.65));
        } catch (\Throwable $e) {
            Log::warning('KnowledgeRetrievalService: similarity search failed, falling back to fulltext', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return $this->findByFulltext($agent, $query, $topK);
        }
    }

    /**
     * Similaridad coseno calculada en PHP pero acotada.
     *
     * Antes: cargaba TODOS los docs (modelos completos, con `content` y el
     * embedding JSON) a memoria y recalculaba la norma de cada doc en cada
     * búsqueda, ignorando la columna `vector_norm` precalculada por
     * GenerateEmbeddingJob. Ahora:
     *  - Fase 1 puntúa con cursor() y solo (id, embedding, vector_norm),
     *    usando la norma almacenada (fallback a recalcular si es null, p.ej.
     *    docs embebidos antes de la migración add_vector_norm).
     *  - Los candidatos se acotan a embeddings.max_candidates (más recientes
     *    primero) para que el coste por búsqueda tenga techo.
     *  - Fase 2 carga los modelos completos solo para el top-K final.
     *
     * Límite conocido: el cálculo sigue siendo O(candidatos × dimensión) en
     * PHP. No usamos funciones vectoriales nativas (VEC_DISTANCE_COSINE)
     * porque el deploy fija `mariadb:11` en docker-compose (soporte vectorial
     * solo desde 11.7+) y `embedding` se persiste como JSON/text, no como
     * columna VECTOR — migrar el esquema es el paso natural si el corpus
     * supera max_candidates de forma habitual.
     */
    private function findBySimilarity(AiAgent $agent, string $query, int $topK, float $minSimilarity): Collection
    {
        $queryEmbedding = $this->embeddingService->embed($query);
        $queryNorm = $this->norm($queryEmbedding);

        if ($queryNorm === 0.0) {
            return collect();
        }

        $maxCandidates = max(1, (int) config('helpdeskagents.embeddings.max_candidates', 2000));

        $candidates = AiAgentKnowledgeBase::query()
            ->where('ai_agent_id', $agent->id)
            ->active()
            ->whereNotNull('embedding')
            ->orderByDesc('id')
            ->limit($maxCandidates)
            ->select(['id', 'embedding', 'vector_norm'])
            ->cursor();

        /** @var array<int, float> $scores doc id => cosine similarity */
        $scores = [];

        foreach ($candidates as $doc) {
            $embedding = is_string($doc->embedding)
                ? json_decode($doc->embedding, true)
                : $doc->embedding;

            if (! is_array($embedding) || $embedding === []) {
                continue;
            }

            $docNorm = $doc->vector_norm !== null
                ? (float) $doc->vector_norm
                : $this->norm($embedding);

            if ($docNorm === 0.0) {
                continue;
            }

            $dot = array_sum(array_map(fn ($x, $y) => $x * $y, $queryEmbedding, $embedding));
            $score = $dot / ($queryNorm * $docNorm);

            if ($score >= $minSimilarity) {
                $scores[$doc->id] = $score;
            }
        }

        arsort($scores);
        $topIds = array_slice(array_keys($scores), 0, $topK);

        if ($topIds === []) {
            return collect();
        }

        $docs = AiAgentKnowledgeBase::query()
            ->whereIn('id', $topIds)
            ->get()
            ->keyBy('id');

        // Preserva el orden por score descendente de la fase 1.
        return collect($topIds)
            ->map(fn (int $id) => $docs->get($id))
            ->filter()
            ->values();
    }

    private function findByFulltext(AiAgent $agent, string $query, int $topK): Collection
    {
        $base = AiAgentKnowledgeBase::query()
            ->where('ai_agent_id', $agent->id)
            ->active();

        if (strlen($query) < 3) {
            return $base->limit($topK)->get();
        }

        return $base->search($query)->limit($topK)->get();
    }

    private function norm(array $vector): float
    {
        return sqrt(array_sum(array_map(fn ($v) => $v ** 2, $vector)));
    }
}
