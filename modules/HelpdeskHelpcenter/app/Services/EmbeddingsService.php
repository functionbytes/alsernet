<?php

namespace Modules\HelpdeskHelpcenter\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\VectorMath;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticleEmbedding;

class EmbeddingsService
{
    /**
     * Generate embeddings for all locales of an article.
     * Returns the number of chunks created.
     */
    public function generateForArticle(HelpCenterArticle $article, string $locale = 'es'): int
    {
        $body = $this->resolveArticleBody($article, $locale);

        if (empty(trim($body))) {
            return 0;
        }

        $chunks = $this->chunkText($body);

        if (empty($chunks)) {
            return 0;
        }

        // Delete existing embeddings for this article + locale before regenerating
        HelpCenterArticleEmbedding::query()
            ->where('article_id', $article->id)
            ->where('locale', $locale)
            ->delete();

        $created = 0;

        foreach ($chunks as $index => $chunk) {
            $result = $this->callEmbeddingApi($chunk);

            if (empty($result['embedding'])) {
                continue;
            }

            HelpCenterArticleEmbedding::create([
                'article_id' => $article->id,
                'locale' => $locale,
                'chunk_index' => $index,
                'chunk_text' => $chunk,
                'embedding' => $result['embedding'],
                'model' => $result['model'],
                'dimensions' => $result['dimensions'],
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Embeddings for the base locale use the article's own body; any other
     * locale must embed its published translation instead — otherwise a
     * search filtered by locale (see search()) would rank the base-language
     * body under a locale it was never written in.
     */
    private function resolveArticleBody(HelpCenterArticle $article, string $locale): string
    {
        $translatedBody = $locale !== config('app.locale')
            ? $article->translationPublished($locale)?->body
            : null;

        return strip_tags($translatedBody ?? $article->body ?? $article->content ?? '');
    }

    /**
     * Split text into overlapping chunks.
     *
     * @return string[]
     */
    public function chunkText(string $text, int $maxTokens = 800, int $overlap = 100): array
    {
        // Rough token estimation: ~4 chars per token
        $maxChars = $maxTokens * 4;
        $overlapChars = $overlap * 4;

        $text = preg_replace('/\s+/', ' ', trim($text));

        if (strlen($text) <= $maxChars) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $length = strlen($text);

        while ($start < $length) {
            $end = min($start + $maxChars, $length);

            // Try to break on sentence boundary
            if ($end < $length) {
                $boundarySearch = substr($text, $start, $maxChars);
                $lastPeriod = max(
                    strrpos($boundarySearch, '. '),
                    strrpos($boundarySearch, ".\n"),
                    strrpos($boundarySearch, '! '),
                    strrpos($boundarySearch, '? '),
                );

                if ($lastPeriod !== false && $lastPeriod > $maxChars * 0.5) {
                    $end = $start + $lastPeriod + 2;
                }
            }

            $chunks[] = trim(substr($text, $start, $end - $start));

            $start = max($start + 1, $end - $overlapChars);
        }

        return array_filter($chunks, fn ($c) => strlen(trim($c)) > 50);
    }

    /**
     * Call the OpenAI Embeddings API for a single text.
     * Returns an empty array when the API key is missing or the request fails.
     *
     * Indexing (generateForArticle, run from a background job) keeps the
     * resilient defaults: a long timeout and retries on transient errors.
     * The interactive widget search instead passes a short timeout and no
     * retries — see queryEmbedding() — because a slow OpenAI response there
     * blocks the customer-facing request, and a retry would only make that
     * wait longer for no benefit.
     *
     * @return array{embedding: list<float>, model: string, dimensions: int}|array{}
     */
    public function callEmbeddingApi(string $text, int $timeoutSeconds = 30, int $retries = 3): array
    {
        $apiKey = config('services.openai.key', '');
        $model = config('services.openai.embedding_model', 'text-embedding-3-small');

        if (empty($apiKey)) {
            Log::warning('EmbeddingsService: OPENAI_API_KEY not configured, skipping embedding generation.');

            return [];
        }

        try {
            $request = Http::timeout($timeoutSeconds)->withToken($apiKey);

            if ($retries > 0) {
                $request = $request->retry($retries, 250, function (\Throwable $exception, PendingRequest $req) {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && in_array($exception->response->status(), [429, 500, 502, 503, 504], true));
                }, throw: false);
            }

            $response = $request->post('https://api.openai.com/v1/embeddings', [
                'model' => $model,
                'input' => $text,
            ]);

            if ($response->failed()) {
                Log::warning('EmbeddingsService: OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $embedding = $data['data'][0]['embedding'] ?? [];

            if (empty($embedding)) {
                return [];
            }

            return [
                'embedding' => $embedding,
                'model' => $data['model'] ?? $model,
                'dimensions' => count($embedding),
            ];
        } catch (\Throwable $e) {
            Log::warning('EmbeddingsService: exception calling OpenAI', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Cosine similarity between two float vectors.
     *
     * @param  float[]  $a
     * @param  float[]  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        return VectorMath::cosine($a, $b);
    }

    /**
     * Embeds a search query for the interactive widget path: short timeout,
     * no retries (see callEmbeddingApi's docblock), and cached for an hour
     * so repeated/autocomplete searches for the same text don't re-bill
     * OpenAI or re-pay its latency on every keystroke-triggered request.
     *
     * Queries shorter than 3 characters are rejected before ever reaching
     * OpenAI — too short to carry any semantic meaning worth embedding.
     *
     * @return array{embedding: list<float>, model: string, dimensions: int}|array{}
     */
    private function queryEmbedding(string $query): array
    {
        $normalized = mb_strtolower(trim($query));

        if (mb_strlen($normalized) <= 2) {
            return [];
        }

        return Cache::remember(
            'hc:qemb:'.sha1($normalized),
            3600,
            fn () => $this->callEmbeddingApi($normalized, timeoutSeconds: 5, retries: 0),
        );
    }

    /**
     * Semantic search: embed query, pre-filter with fulltext, rank by cosine similarity.
     *
     * @return array<int, array{article_id: int, similarity: float, chunk_text: string}>
     */
    public function search(string $query, int $limit = 10, ?string $locale = null): array
    {
        if (! helpdesk_helpcenter_enabled()) {
            return [];
        }

        $queryEmbedding = $this->queryEmbedding($query);

        if (empty($queryEmbedding['embedding'])) {
            return [];
        }

        // Pre-filter: fulltext search for top 50 candidates
        $builder = HelpCenterArticleEmbedding::query()
            ->selectRaw('*, MATCH(chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance', [$query])
            ->whereRaw('MATCH(chunk_text) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query])
            ->limit(50);

        if ($locale !== null) {
            $builder->where('locale', $locale);
        }

        $candidates = $builder->get();

        if ($candidates->isEmpty()) {
            // Fallback: no fulltext match, use recent embeddings directly
            $builder = HelpCenterArticleEmbedding::query()->limit(50);
            if ($locale !== null) {
                $builder->where('locale', $locale);
            }
            $candidates = $builder->latest()->get();
        }

        // Rank by cosine similarity in PHP
        $ranked = $candidates->map(function (HelpCenterArticleEmbedding $row) use ($queryEmbedding) {
            return [
                'article_id' => $row->article_id,
                'similarity' => $this->cosineSimilarity($queryEmbedding['embedding'], $row->embedding ?? []),
                'chunk_text' => $row->chunk_text,
            ];
        })->sortByDesc('similarity')->take($limit)->values()->toArray();

        return $ranked;
    }
}
