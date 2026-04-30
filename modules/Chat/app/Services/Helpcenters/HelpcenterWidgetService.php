<?php

namespace Modules\Chat\Services\Helpcenters;

use Illuminate\Support\Str;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;

class HelpcenterWidgetService
{
    /**
     * Return a single published article formatted for the widget.
     *
     * @return array{id: string, title: string, body: string, description: string, category: string, section: string|null}
     */
    public function getArticle(int $id): array
    {
        $article = HelpcenterArticle::published()
            ->with('categories')
            ->findOrFail($id);

        $category = $article->categories->first();

        return [
            'id' => (string) $article->id,
            'title' => $article->title,
            'body' => $article->body,
            'description' => $article->description,
            'category' => $category->parent->name ?? 'General',
            'section' => $category->name ?? null,
        ];
    }

    /**
     * Return all categories and articles formatted for the widget.
     *
     * @return array{categories: list<array{id: string, name: string, icon: string, count: int}>, articles: list<array{id: string, title: string, excerpt: string, category: string, section: string}>}
     */
    public function getWidgetData(): array
    {
        $categories = HelpcenterCategory::categories()
            ->with(['sections.articles' => fn ($q) => $q->published()->orderBy('id', 'desc')])
            ->ordered()
            ->get();

        $widgetCategories = [];
        $widgetArticles = [];

        foreach ($categories as $category) {
            $articleCount = 0;

            foreach ($category->sections as $section) {
                $articleCount += $section->articles->count();

                foreach ($section->articles as $article) {
                    $widgetArticles[] = [
                        'id' => (string) $article->id,
                        'title' => $article->title,
                        'excerpt' => $article->description ?: Str::limit(strip_tags($article->body), 100),
                        'category' => $category->name,
                        'section' => $section->name,
                    ];
                }
            }

            if ($articleCount > 0) {
                $widgetCategories[] = [
                    'id' => (string) $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon ?: '📄',
                    'count' => $articleCount,
                ];
            }
        }

        return [
            'categories' => $widgetCategories,
            'articles' => $widgetArticles,
        ];
    }
}
