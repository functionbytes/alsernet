<?php

use Illuminate\Database\Eloquent\Collection;
use Modules\Blog\Models\BlogPost;

if (! function_exists('get_related_posts')) {
    /**
     * Obtiene posts relacionados por categoría.
     */
    function get_related_posts(int $postId, int $limit = 4): Collection
    {
        $post = BlogPost::query()->with('categories')->find($postId);

        if (! $post) {
            return collect();
        }

        $categoryIds = $post->categories->pluck('id')->toArray();

        return BlogPost::query()
            ->published()
            ->where('id', '!=', $postId)
            ->when($categoryIds, fn ($q) => $q->whereHas('categories', fn ($q2) => $q2->whereIn('blog_categories.id', $categoryIds)))
            ->with(['categories', 'user'])
            ->recent()
            ->limit($limit)
            ->get();
    }
}

if (! function_exists('get_recent_posts')) {
    /**
     * Obtiene los posts más recientes publicados.
     */
    function get_recent_posts(int $limit = 5): Collection
    {
        return BlogPost::query()
            ->published()
            ->with(['categories', 'user'])
            ->recent()
            ->limit($limit)
            ->get();
    }
}
