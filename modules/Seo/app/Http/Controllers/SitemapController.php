<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogTag;
use Modules\Page\Models\Page;
use Modules\Seo\Models\SeoMeta;

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
        if (class_exists(Page::class)) {
            $sitemap->addModel(Page::class);
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Display posts sitemap (blog posts, categories, and tags)
     */
    public function posts()
    {
        $sitemap = app('sitemap');
        $sitemap->clear();

        if (class_exists(BlogPost::class)) {
            $sitemap->addModel(BlogPost::class);
        }

        if (class_exists(BlogCategory::class)) {
            foreach (BlogCategory::published()->get() as $category) {
                $sitemap->add($category->url, $category->updated_at->toAtomString(), '0.6', 'weekly');
            }
        }

        if (class_exists(BlogTag::class)) {
            foreach (BlogTag::published()->get() as $tag) {
                $sitemap->add($tag->url, $tag->updated_at->toAtomString(), '0.4', 'monthly');
            }
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

        $sitemap->addSitemap(route('sitemap.pages'));
        $sitemap->addSitemap(route('sitemap.posts'));

        return response($sitemap->render('index'), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Display image sitemap
     */
    public function images(): Response
    {
        $metas = SeoMeta::whereNotNull('og_image')
            ->where('og_image', '!=', '')
            ->select(['id', 'og_image', 'title', 'canonical_url'])
            ->get();

        $content = view('Seo::sitemaps.images', compact('metas'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Display video sitemap
     */
    public function videos(): Response
    {
        $metas = SeoMeta::whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->select(['id', 'title', 'description', 'canonical_url', 'og_image'])
            ->get()
            ->filter(fn ($meta) => ! empty($meta->canonical_url));

        $content = view('Seo::sitemaps.videos', compact('metas'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Display news sitemap (posts published in the last 2 days)
     */
    public function news(): Response
    {
        $posts = collect();

        $blogPostClass = 'Modules\\Blog\\Models\\BlogPost';
        if (class_exists($blogPostClass)) {
            $posts = $blogPostClass::query()
                ->where('published_at', '>=', now()->subDays(2))
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->limit(1000)
                ->get(['id', 'title', 'slug', 'published_at']);
        }

        $siteName = config('app.name', 'My Site');
        $language = config('app.locale', 'es');
        $content = view('Seo::sitemaps.news', compact('posts', 'siteName', 'language'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Generate sitemap with all models
     */
    protected function generateSitemap($sitemap)
    {
        $sitemap->add(url('/'), now()->toAtomString(), '1.0', 'daily');

        foreach (config('sitemap.models', []) as $modelClass) {
            if (class_exists($modelClass)) {
                $sitemap->addModel($modelClass);
            }
        }

        if (class_exists(BlogPost::class)) {
            $sitemap->addModel(BlogPost::class);
        }

        if (class_exists(BlogCategory::class)) {
            foreach (BlogCategory::published()->get() as $category) {
                $sitemap->add($category->url, $category->updated_at->toAtomString(), '0.6', 'weekly');
            }
        }

        if (class_exists(BlogTag::class)) {
            foreach (BlogTag::published()->get() as $tag) {
                $sitemap->add($tag->url, $tag->updated_at->toAtomString(), '0.4', 'monthly');
            }
        }

        return response($sitemap->render(), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
