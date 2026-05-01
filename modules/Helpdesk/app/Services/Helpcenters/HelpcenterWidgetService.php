<?php

namespace Modules\Helpdesk\Services\Helpcenters;

use Illuminate\Support\Str;
use Modules\Helpdesk\Models\HelpCenterArticle;
use Modules\Helpdesk\Models\HelpCenterCategory;

class HelpcenterWidgetService
{
    /**
     * Categories + popular articles in widget-friendly shape.
     *
     * @return array{categories: array<int, array<string, mixed>>, popular_articles: array<int, array<string, mixed>>, articles: array<int, array<string, mixed>>}
     */
    public function getWidgetData(): array
    {
        $categoriesQuery = HelpCenterCategory::query();
        if (in_array('parent_id', \Schema::connection('helpdesk')->getColumnListing('helpdesk_helpcenter_categories'), true)) {
            $categoriesQuery->whereNull('parent_id');
        }

        $categories = $categoriesQuery->orderBy('order')->get()->map(fn (HelpCenterCategory $c) => [
            'id' => (string) $c->id,
            'name' => $c->name,
            'slug' => $c->slug ?? null,
            'icon' => $c->icon ?? null,
            'count' => HelpCenterArticle::where('category_id', $c->id)
                ->where('is_published', true)
                ->where('active', true)
                ->count(),
        ])->values()->all();

        $popular = HelpCenterArticle::where('is_published', true)
            ->where('active', true)
            ->orderByDesc('views_count')
            ->limit(5)
            ->get()
            ->map(fn (HelpCenterArticle $a) => $this->articleToArray($a))
            ->values()
            ->all();

        $articles = HelpCenterArticle::where('is_published', true)
            ->where('active', true)
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

    public function getArticle(int $id): ?array
    {
        $article = HelpCenterArticle::where('is_published', true)
            ->where('active', true)
            ->find($id);

        if (! $article) {
            return null;
        }

        if (\Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'views_count')) {
            $article->increment('views_count');
        }

        return [
            'id' => (string) $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'body' => $article->content ?? '',
            'description' => $article->excerpt ?? '',
            'category' => $article->category?->name,
            'section' => null,
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

        $cols = \Schema::connection('helpdesk')->getColumnListing('helpdesk_helpcenter_articles');
        $column = $helpful ? 'helpful_count' : 'unhelpful_count';

        if (! in_array($column, $cols, true)) {
            // Column does not exist yet — treat as no-op success.
            return true;
        }

        $article->increment($column);

        return true;
    }

    private function articleToArray(HelpCenterArticle $a): array
    {
        return [
            'id' => (string) $a->id,
            'title' => $a->title,
            'excerpt' => $a->excerpt ?? Str::limit(strip_tags($a->content ?? ''), 100),
            'category' => $a->category?->name ?? '',
            'section' => null,
        ];
    }
}
