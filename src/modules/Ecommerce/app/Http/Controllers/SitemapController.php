<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\LegalPage;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('ecommerce_sitemap_xml', 3600, fn () => $this->generate());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function generate(): string
    {
        $urls = [];

        $urls[] = $this->urlEntry(url('/'), now(), 'daily', '1.0');
        $urls[] = $this->urlEntry(route('shop.index'), now(), 'daily', '0.9');

        ProductCategory::query()
            ->where('status', 'published')
            ->each(function (ProductCategory $cat) use (&$urls): void {
                $urls[] = $this->urlEntry(route('shop.category', $cat->slug), $cat->updated_at, 'weekly', '0.7');
            });

        Brand::query()
            ->where('status', 'published')
            ->each(function (Brand $brand) use (&$urls): void {
                $urls[] = $this->urlEntry(route('shop.brand', $brand->slug), $brand->updated_at, 'weekly', '0.6');
            });

        Product::query()
            ->where('status', 'published')
            ->where('is_variation', false)
            ->select('slug', 'updated_at')
            ->chunk(500, function ($products) use (&$urls): void {
                foreach ($products as $product) {
                    $urls[] = $this->urlEntry(route('shop.product', $product->slug), $product->updated_at, 'weekly', '0.8');
                }
            });

        LegalPage::query()
            ->where('is_published', true)
            ->each(function (LegalPage $page) use (&$urls): void {
                $urls[] = $this->urlEntry(route('shop.legal.show', $page->slug), $page->updated_at, 'monthly', '0.4');
            });

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode("\n", $urls)."\n"
            .'</urlset>';
    }

    private function urlEntry(string $url, mixed $lastmod, string $changefreq, string $priority): string
    {
        $lastmod = $lastmod ? $lastmod->toIso8601String() : now()->toIso8601String();

        return "  <url>\n"
            .'    <loc>'.htmlspecialchars($url, ENT_XML1)."</loc>\n"
            ."    <lastmod>{$lastmod}</lastmod>\n"
            ."    <changefreq>{$changefreq}</changefreq>\n"
            ."    <priority>{$priority}</priority>\n"
            .'  </url>';
    }
}
