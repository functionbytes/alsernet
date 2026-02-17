<?php

namespace Modules\Seo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Modules\Seo\Builder\SitemapBuilder add(string $loc, ?string $lastmod = null, string $priority = '0.5', string $changefreq = 'weekly')
 * @method static \Modules\Seo\Builder\SitemapBuilder addModel($model)
 * @method static \Modules\Seo\Builder\SitemapBuilder addSitemap(string $loc, ?string $lastmod = null)
 * @method static string render(string $format = 'xml')
 * @method static void generate()
 * @method static array getItems()
 * @method static array getSitemaps()
 * @method static \Modules\Seo\Builder\SitemapBuilder clear()
 *
 * @see \Modules\Seo\Builder\SitemapBuilder
 */
class Sitemap extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sitemap';
    }
}
