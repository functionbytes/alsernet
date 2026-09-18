<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseSearchService
{
    /**
     * Search for relevant KB articles based on comment body and intent.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, ?string $intent = null, int $limit = 3): array
    {
        $articleModel = $this->resolveArticleModel();

        if ($articleModel === null) {
            return [];
        }

        try {
            $searchTerms = $this->extractSearchTerms($query, $intent);

            $results = $articleModel::query()
                ->when(method_exists($articleModel, 'scopePublished'), fn (Builder $q) => $q->published())
                ->where(function (Builder $q) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $q->orWhere('title', 'like', "%{$term}%")
                            ->orWhere('body', 'like', "%{$term}%")
                            ->orWhere('excerpt', 'like', "%{$term}%");
                    }
                })
                ->limit($limit)
                ->get();

            return $results->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'url' => $this->resolveArticleUrl($article),
                    'excerpt' => $this->buildExcerpt($article),
                ];
            })->all();
        } catch (\Throwable $e) {
            Log::error('KnowledgeBaseSearchService: search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check if a KB module is available.
     */
    public function hasKnowledgeBase(): bool
    {
        return $this->resolveArticleModel() !== null;
    }

    /**
     * Resolve the Article model class if available.
     *
     * @return class-string|null
     */
    private function resolveArticleModel(): ?string
    {
        if (class_exists('Modules\Blog\Models\Article')) {
            return 'Modules\Blog\Models\Article';
        }

        if (class_exists('Modules\Helpdesk\Models\Article')) {
            return 'Modules\Helpdesk\Models\Article';
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function extractSearchTerms(string $query, ?string $intent = null): array
    {
        $terms = array_filter(
            explode(' ', mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query)))
        );

        // Remove common stop words
        $stopWords = ['el', 'la', 'los', 'las', 'de', 'del', 'en', 'y', 'a', 'un', 'una', 'es', 'son', 'the', 'a', 'an', 'and', 'or', 'in', 'on', 'at', 'to', 'for'];
        $terms = array_diff($terms, $stopWords);

        if ($intent && ! in_array(mb_strtolower($intent), $terms, true)) {
            $terms[] = mb_strtolower($intent);
        }

        return array_slice($terms, 0, 10);
    }

    /**
     * @param  mixed  $article
     */
    private function resolveArticleUrl($article): ?string
    {
        if (method_exists($article, 'getUrl')) {
            return $article->getUrl();
        }

        if (property_exists($article, 'slug') && $article->slug && \Illuminate\Support\Facades\Route::has('blog.article.show')) {
            return route('blog.article.show', $article->slug, false);
        }

        return null;
    }

    /**
     * @param  mixed  $article
     */
    private function buildExcerpt($article): string
    {
        if (property_exists($article, 'excerpt') && $article->excerpt) {
            return $article->excerpt;
        }

        if (property_exists($article, 'body') && $article->body) {
            return mb_substr(strip_tags($article->body), 0, 160).'...';
        }

        return '';
    }
}
