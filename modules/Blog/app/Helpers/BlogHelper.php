<?php

use Illuminate\Database\Eloquent\Collection;
use Modules\Blog\Models\BlogPost;

if (! function_exists('clean_html')) {
    /**
     * Sanitize untrusted HTML using HTMLPurifier, preserving safe formatting tags.
     */
    function clean_html(?string $dirty): string
    {
        if ($dirty === null || $dirty === '') {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,del,ins,a[href|title|target|rel],ul,ol,li,blockquote,pre,code,h1,h2,h3,h4,h5,h6,img[src|alt|title|width|height],table,thead,tbody,tr,th,td,span[class|style],div[class],hr,sub,sup');
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'font-weight', 'font-style', 'text-decoration', 'text-align']);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.RemoveEmpty', false);
        $config->set('Cache.DefinitionImpl', null);

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($dirty);
    }
}

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
