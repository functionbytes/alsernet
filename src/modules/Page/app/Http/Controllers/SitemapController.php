<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Models\BlogPost;
use Modules\Page\Models\Page;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'sitemap.xml';

    private const CACHE_TTL = 21600; // 6 hours

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildXml());

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    private function buildXml(): string
    {
        $prefix = setting('permalink-modules-page-models-page', '');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'."\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        // Homepage
        $xml .= $this->urlEntry(url('/'), now()->toAtomString(), 'daily', '1.0');

        // Published pages
        $xml .= $this->buildPagesXml($prefix);

        // Published blog posts
        $xml .= $this->buildBlogPostsXml();

        $xml .= '</urlset>';

        return $xml;
    }

    private function buildPagesXml(string $prefix): string
    {
        $xml = '';

        Page::published()
            ->with(['translations' => function ($q) {
                $q->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            }])
            ->chunk(100, function ($pages) use ($prefix, &$xml) {
                foreach ($pages as $page) {
                    $pubTrans = $page->translations;

                    if ($pubTrans->isNotEmpty()) {
                        foreach ($pubTrans as $trans) {
                            $loc = url($prefix ? $prefix.'/'.$trans->slug : $trans->slug);
                            $lastmod = ($trans->updated_at ?? $page->updated_at)->toAtomString();

                            $xml .= "    <url>\n";
                            $xml .= '        <loc>'.e($loc)."</loc>\n";
                            $xml .= "        <lastmod>{$lastmod}</lastmod>\n";
                            $xml .= "        <changefreq>weekly</changefreq>\n";
                            $xml .= "        <priority>0.8</priority>\n";

                            if ($pubTrans->count() > 1) {
                                foreach ($pubTrans as $alt) {
                                    $altUrl = url($prefix ? $prefix.'/'.$alt->slug : $alt->slug);
                                    $xml .= "        <xhtml:link rel=\"alternate\" hreflang=\"{$alt->locale}\" href=\"".e($altUrl)."\"/>\n";
                                }
                                $defaultUrl = url($prefix ? $prefix.'/'.$pubTrans->first()->slug : $pubTrans->first()->slug);
                                $xml .= '        <xhtml:link rel="alternate" hreflang="x-default" href="'.e($defaultUrl)."\"/>\n";
                            }

                            $xml .= "    </url>\n";
                        }
                    } else {
                        $loc = url($prefix ? $prefix.'/'.$page->slug : $page->slug);
                        $xml .= $this->urlEntry($loc, $page->updated_at->toAtomString(), 'weekly', '0.8');
                    }
                }
            });

        return $xml;
    }

    private function buildBlogPostsXml(): string
    {
        if (! class_exists(BlogPost::class)) {
            return '';
        }

        $xml = '';

        BlogPost::published()
            ->select(['id', 'slug', 'updated_at', 'published_at'])
            ->chunk(100, function ($posts) use (&$xml) {
                foreach ($posts as $post) {
                    $xml .= $this->urlEntry(
                        url('blog/'.$post->slug),
                        $post->updated_at->toAtomString(),
                        'monthly',
                        '0.6'
                    );
                }
            });

        return $xml;
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "    <url>\n"
            .'        <loc>'.e($loc)."</loc>\n"
            ."        <lastmod>{$lastmod}</lastmod>\n"
            ."        <changefreq>{$changefreq}</changefreq>\n"
            ."        <priority>{$priority}</priority>\n"
            ."    </url>\n";
    }
}
