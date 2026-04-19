<?php

namespace Modules\Sitemap\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Sitemap\Builder\SitemapBuilder;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $cacheKey = 'sitemap-xml';
        $duration = config('sitemap.cache_duration', 86400);

        $xml = config('sitemap.cache_enabled', true)
            ? cache()->remember($cacheKey, $duration, fn () => $this->buildMainXml())
            : $this->buildMainXml();

        return $this->xmlResponse($xml);
    }

    public function pages(): Response
    {
        $builder = new SitemapBuilder;

        foreach (config('sitemap.page_models', []) as $modelClass) {
            if (class_exists($modelClass)) {
                $builder->addModel($modelClass);
            }
        }

        return $this->xmlResponse($builder->render());
    }

    public function posts(): Response
    {
        $builder = new SitemapBuilder;

        foreach (config('sitemap.post_models', []) as $modelClass) {
            if (class_exists($modelClass)) {
                $builder->addModel($modelClass);
            }
        }

        foreach (config('sitemap.post_callbacks', []) as $callback) {
            if (is_callable($callback)) {
                $callback($builder);
            }
        }

        return $this->xmlResponse($builder->render());
    }

    public function sitemapIndex(): Response
    {
        $builder = new SitemapBuilder;

        $hasPages = ! empty(config('sitemap.page_models'));
        $hasPosts = ! empty(config('sitemap.post_models')) || ! empty(config('sitemap.post_callbacks'));

        if ($hasPages) {
            $builder->addSitemap(url('/sitemap-pages.xml'));
        }

        if ($hasPosts) {
            $builder->addSitemap(url('/sitemap-posts.xml'));
        }

        if (! $hasPages && ! $hasPosts) {
            $builder->addSitemap(url('/sitemap.xml'));
        }

        foreach (config('sitemap.index_extra', []) as $extraUrl) {
            $builder->addSitemap($extraUrl);
        }

        return $this->xmlResponse($builder->render('index'));
    }

    private function buildMainXml(): string
    {
        $builder = new SitemapBuilder;

        $builder->add(url('/'), null, '1.0', 'daily');

        foreach (config('sitemap.static_urls', []) as $entry) {
            $builder->add(
                url($entry['loc']),
                null,
                $entry['priority'] ?? '0.5',
                $entry['changefreq'] ?? 'weekly'
            );
        }

        foreach (config('sitemap.models', []) as $modelClass) {
            if (class_exists($modelClass)) {
                $builder->addModel($modelClass);
            }
        }

        return $builder->render();
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Last-Modified' => now()->toRfc7231String(),
            'Cache-Control' => 'public, max-age='.config('sitemap.cache_duration', 86400),
        ]);
    }
}
