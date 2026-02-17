<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;

class SitemapController extends Controller
{
    /**
     * Display the main sitemap
     */
    public function index()
    {
        $sitemap = app('sitemap');
        $sitemap->clear();

        $cacheKey = 'sitemap-xml';
        $cacheDuration = config('sitemap.cache_duration', 86400);

        // Cache por 24 horas
        if (config('sitemap.cache_enabled', true)) {
            return cache()->remember($cacheKey, $cacheDuration, function () use ($sitemap) {
                return $this->generateSitemap($sitemap);
            });
        }

        return $this->generateSitemap($sitemap);
    }

    /**
     * Display pages sitemap
     */
    public function pages()
    {
        $sitemap = app('sitemap');
        $sitemap->clear();

        // Add only pages
        if (class_exists(\Modules\Page\Models\Page::class)) {
            $sitemap->addModel(\Modules\Page\Models\Page::class);
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Display posts sitemap
     */
    public function posts()
    {
        $sitemap = app('sitemap');
        $sitemap->clear();

        // Add only posts
        if (class_exists(\Modules\Post\Models\Post::class)) {
            $sitemap->addModel(\Modules\Post\Models\Post::class);
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Display sitemap index
     */
    public function sitemapIndex()
    {
        $sitemap = app('sitemap');
        $sitemap->clear();

        // Add individual sitemaps
        $sitemap->addSitemap(route('sitemap.pages'));
        $sitemap->addSitemap(route('sitemap.posts'));

        return response($sitemap->render('index'), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate sitemap with all models
     */
    protected function generateSitemap($sitemap)
    {
        // Add homepage
        $sitemap->add(url('/'), now()->toAtomString(), '1.0', 'daily');

        // Add models from config
        $models = config('sitemap.models', []);

        foreach ($models as $modelClass) {
            if (class_exists($modelClass)) {
                $sitemap->addModel($modelClass);
            }
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
