<?php

namespace Modules\HelpdeskHelpcenter\Services;

use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Concerns\BuildsFulltextSearch;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;

class HelpcenterWidgetService
{
    use BuildsFulltextSearch;

    /**
     * @return array{categories: array<int, array<string, mixed>>, popular_articles: array<int, array<string, mixed>>, articles: array<int, array<string, mixed>>}
     */
    public function getWidgetData(): array
    {
        // Count published+active articles per pivot category_id (any depth).
        // Articles created via the manager have category_id=NULL (pivot manages associations),
        // so we must count via the helpdesk_helpcenter_category_article pivot, not category_id.
        $pivotCounts = HelpCenterCategory::query()
            ->withCount(['articles as published_count' => fn ($q) => $q
                ->where('is_published', true)
                ->where('active', true)])
            ->get()
            ->pluck('published_count', 'id');

        // For root categories aggregate direct count + all children (sections) counts.
        $categories = HelpCenterCategory::query()
            ->whereNull('parent_id')
            ->with('sections:id,parent_id')
            ->orderBy('position')
            ->get()
            ->map(function (HelpCenterCategory $c) use ($pivotCounts) {
                $sectionIds = $c->sections->pluck('id');
                $count = (int) ($pivotCounts[$c->id] ?? 0)
                    + $sectionIds->sum(fn (int $id) => (int) ($pivotCounts[$id] ?? 0));

                return [
                    'id' => (string) $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug ?? null,
                    'icon' => $c->icon ?? null,
                    'count' => $count,
                ];
            })
            ->values()
            ->all();

        $popular = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where('active', true)
            ->with('categories.parent')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get()
            ->map(fn (HelpCenterArticle $a) => $this->articleToArray($a))
            ->values()
            ->all();

        $articles = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where('active', true)
            ->with('categories.parent')
            ->orderByDesc('views_count')
            ->limit(50)
            ->get()
            ->map(fn (HelpCenterArticle $a) => $this->articleToArray($a))
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'popular_articles' => $popular,
            'articles' => $articles,
        ];
    }

    /**
     * @return array{id: string, title: string, slug: string, excerpt: string, url: string}[]
     */
    public function searchArticles(string $query, string $locale = ''): array
    {
        $booleanTerm = $this->buildBooleanTerm($query);

        $builder = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where('active', true)
            ->where(fn ($q) => $q
                ->when($booleanTerm !== null, fn ($qb) => $qb
                    ->whereRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE)', [$booleanTerm]))
                ->orWhere('title', 'like', "%{$query}%")
                ->orWhere('body', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%")
                ->orWhereHas('translations', fn ($qt) => $qt
                    ->when($locale, fn ($ql) => $ql->where('locale', $locale))
                    ->where(fn ($qtt) => $qtt->where('title', 'like', "%{$query}%")->orWhere('body', 'like', "%{$query}%"))
                )
            )
            ->when($booleanTerm !== null, fn ($q) => $q
                ->orderByRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanTerm]))
            ->limit(10)
            ->get();

        return $builder
            ->map(fn (HelpCenterArticle $a) => [
                'id' => (string) $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => $a->excerpt ?? Str::limit(strip_tags($a->content ?? $a->body ?? ''), 100),
                'url' => route('public.helpcenter.show', $a->slug),
            ])
            ->values()
            ->all();
    }

    public function getArticle(int $id): ?array
    {
        $article = HelpCenterArticle::query()
            ->where('is_published', true)
            ->where('active', true)
            ->with('categories.parent')
            ->find($id);

        if (! $article) {
            return null;
        }

        $article->incrementQuietly('views_count');

        [$category, $section] = $this->resolveCategorySection($article);

        return [
            'id' => (string) $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'body' => $article->content ?? $article->body ?? '',
            'description' => $article->excerpt ?? $article->description ?? '',
            'category' => $category,
            'section' => $section,
            'helpful_count' => (int) ($article->helpful_count ?? 0),
            'unhelpful_count' => (int) ($article->unhelpful_count ?? 0),
        ];
    }

    public function recordFeedback(int $id, bool $helpful): bool
    {
        $article = HelpCenterArticle::find($id);

        if (! $article) {
            return false;
        }

        $column = $helpful ? 'helpful_count' : 'unhelpful_count';

        $article->increment($column);

        return true;
    }

    private function articleToArray(HelpCenterArticle $a): array
    {
        [$category, $section] = $this->resolveCategorySection($a);

        return [
            'id' => (string) $a->id,
            'title' => $a->title,
            'excerpt' => $a->excerpt ?? Str::limit(strip_tags($a->content ?? $a->body ?? ''), 100),
            'category' => $category,
            'section' => $section,
        ];
    }

    /**
     * Resolves category and section names from the article's first associated category.
     *
     * If the first category has `is_section = true`, it is treated as a section
     * whose parent is the category. Otherwise it is treated as the category directly.
     *
     * @return array{0: string|null, 1: string|null} [$category, $section]
     */
    private function resolveCategorySection(HelpCenterArticle $article): array
    {
        $first = $article->categories->first();

        if (! $first) {
            return [null, null];
        }

        if ($first->is_section) {
            return [$first->parent?->name, $first->name];
        }

        return [$first->name, null];
    }
}
