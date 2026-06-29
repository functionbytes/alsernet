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

    private function findBySimilarity(AiAgent $agent, string $query, int $topK, float $minSimilarity): Collection
    {
        $queryEmbedding = $this->embeddingService->embed($query);

        $docs = AiAgentKnowledgeBase::query()
            ->where('ai_agent_id', $agent->id)
            ->active()
            ->whereNotNull('embedding')
            ->get();

        return $docs
            ->map(function (AiAgentKnowledgeBase $doc) use ($queryEmbedding): array {
                $embedding = is_string($doc->embedding)
                    ? json_decode($doc->embedding, true)
                    : $doc->embedding;

                return [
                    'doc' => $doc,
                    'score' => $this->cosineSimilarity($queryEmbedding, $embedding),
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] >= $minSimilarity)
            ->sortByDesc('score')
            ->take($topK)
            ->pluck('doc');
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

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = array_sum(array_map(fn ($x, $y) => $x * $y, $a, $b));
        $normA = $this->norm($a);
        $normB = $this->norm($b);

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / ($normA * $normB);
    }

    private function norm(array $vector): float
    {
        return sqrt(array_sum(array_map(fn ($v) => $v ** 2, $vector)));
    }
}
