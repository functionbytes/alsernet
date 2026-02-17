<?php

namespace Modules\Sitemap\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sitemap\Builder\SitemapBuilder;
use Modules\Sitemap\Facades\Sitemap;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_route_is_accessible()
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/xml');
    }

    public function test_sitemap_builder_can_add_urls()
    {
        $sitemap = new SitemapBuilder;

        $sitemap->add('https://example.com', now()->toAtomString(), '0.8', 'daily');

        $this->assertCount(1, $sitemap->getItems());
    }

    public function test_sitemap_builder_can_clear_items()
    {
        $sitemap = new SitemapBuilder;

        $sitemap->add('https://example.com', now()->toAtomString(), '0.8', 'daily');
        $this->assertCount(1, $sitemap->getItems());

        $sitemap->clear();
        $this->assertCount(0, $sitemap->getItems());
    }

    public function test_sitemap_builder_can_add_sitemaps()
    {
        $sitemap = new SitemapBuilder;

        $sitemap->addSitemap('https://example.com/sitemap-pages.xml');

        $this->assertCount(1, $sitemap->getSitemaps());
    }

    public function test_sitemap_builder_renders_xml()
    {
        $sitemap = new SitemapBuilder;
        $sitemap->add('https://example.com', now()->toAtomString(), '0.8', 'daily');

        $xml = $sitemap->render();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('https://example.com', $xml);
    }

    public function test_sitemap_command_exists()
    {
        $this->artisan('sitemap:generate')
            ->assertSuccessful();
    }

    public function test_sitemap_facade_works()
    {
        Sitemap::clear();
        Sitemap::add('https://example.com', now()->toAtomString(), '0.8', 'daily');

        $this->assertCount(1, Sitemap::getItems());

        Sitemap::clear();
    }

    public function test_sitemap_index_renders_correctly()
    {
        $sitemap = new SitemapBuilder;
        $sitemap->addSitemap('https://example.com/sitemap-pages.xml');
        $sitemap->addSitemap('https://example.com/sitemap-posts.xml');

        $xml = $sitemap->render('index');

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<sitemapindex', $xml);
        $this->assertStringContainsString('sitemap-pages.xml', $xml);
        $this->assertStringContainsString('sitemap-posts.xml', $xml);
    }
}
