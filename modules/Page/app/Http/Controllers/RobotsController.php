<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $prefix = setting('permalink-modules-page-models-page', '');

        // Recopilar URLs con noindex
        $noindexUrls = [];

        // Traducciones con noindex
        PageTranslation::where('seo_noindex', true)
            ->where('status', 'published')
            ->get(['slug', 'locale'])
            ->each(function ($trans) use (&$noindexUrls, $prefix) {
                $noindexUrls[] = '/'.($prefix ? $prefix.'/'.$trans->slug : $trans->slug);
            });

        // Páginas base con noindex (sin traducción)
        Page::where('seo_noindex', true)
            ->published()
            ->get(['slug'])
            ->each(function ($page) use (&$noindexUrls, $prefix) {
                $noindexUrls[] = '/'.($prefix ? $prefix.'/'.$page->slug : $page->slug);
            });

        $sitemapUrl = url('/sitemap.xml');

        $content = "User-agent: *\n";
        $content .= "Allow: /\n";

        foreach (array_unique($noindexUrls) as $url) {
            $content .= "Disallow: {$url}\n";
        }

        $content .= "\n";
        $content .= "Sitemap: {$sitemapUrl}\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
