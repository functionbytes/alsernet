<?php

namespace Modules\Sitemap\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Sitemap\Builder\SitemapBuilder;
use Modules\Sitemap\Facades\Sitemap;
use Modules\Sitemap\Helpers\SitemapHelper;
use Modules\Sitemap\Http\Controllers\SitemapController;
use Modules\Sitemap\Traits\HasSitemapItems;
use OverflowException;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aísla tests de modelos registrados por otros módulos (ej. Seo).
        config(['sitemap.models' => []]);
        config(['sitemap.page_models' => []]);
        config(['sitemap.post_models' => []]);
        config(['sitemap.post_callbacks' => []]);
        config(['sitemap.static_urls' => []]);
        config(['sitemap.cache_enabled' => false]);

        // La tabla puede desaparecer cuando otros tests usan RefreshDatabase.
        Schema::dropIfExists('sitemap_generations');
        Schema::create('sitemap_generations', function ($table) {
            $table->id();
            $table->string('status', 20);
            $table->unsignedInteger('url_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->string('source', 20)->default('command');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();
        });
    }

    // ─────────────────────────────────────────────
    // Controller (directo, sin routing para evitar
    // conflicto con Modules\Page\SitemapController)
    // ─────────────────────────────────────────────

    private function controller(): SitemapController
    {
        return app(SitemapController::class);
    }

    public function test_sitemap_returns_xml_response(): void
    {
        $response = $this->controller()->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', $response->headers->get('content-type'));
        $this->assertStringContainsString('<?xml', $response->getContent());
        $this->assertStringContainsString('<urlset', $response->getContent());
    }

    public function test_sitemap_pages_returns_xml_response(): void
    {
        $response = $this->controller()->pages();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', $response->headers->get('content-type'));
    }

    public function test_sitemap_posts_returns_xml_response(): void
    {
        $response = $this->controller()->posts();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/xml', $response->headers->get('content-type'));
    }

    public function test_sitemap_index_returns_sitemapindex(): void
    {
        $response = $this->controller()->sitemapIndex();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<sitemapindex', $response->getContent());
    }

    public function test_sitemap_response_includes_cache_control_header(): void
    {
        $response = $this->controller()->index();

        $this->assertNotEmpty($response->headers->get('cache-control'));
    }

    public function test_sitemap_response_includes_last_modified_header(): void
    {
        $response = $this->controller()->index();

        $this->assertNotEmpty($response->headers->get('last-modified'));
    }

    // ─────────────────────────────────────────────
    // Builder
    // ─────────────────────────────────────────────

    public function test_sitemap_builder_can_add_urls(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com', now()->toAtomString(), '0.8', 'daily');

        $this->assertCount(1, $builder->getItems());
    }

    public function test_sitemap_builder_sanitizes_invalid_priority(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com', null, '9.9', 'daily');

        $this->assertSame('0.5', $builder->getItems()[0]['priority']);
    }

    public function test_sitemap_builder_sanitizes_invalid_changefreq(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com', null, '0.5', 'every-second');

        $this->assertSame('weekly', $builder->getItems()[0]['changefreq']);
    }

    public function test_sitemap_builder_throws_when_max_items_exceeded(): void
    {
        config(['sitemap.max_items' => 5]);

        $this->expectException(OverflowException::class);

        $builder = new SitemapBuilder;

        for ($i = 0; $i <= 5; $i++) {
            $builder->add("https://example.com/page/{$i}");
        }
    }

    public function test_sitemap_builder_deduplicates_duplicate_urls(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com/page');
        $builder->add('https://example.com/page');
        $builder->add('https://example.com/page');

        $this->assertCount(1, $builder->getItems());
    }

    public function test_sitemap_builder_stores_null_lastmod_when_not_provided(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com/page');

        $this->assertNull($builder->getItems()[0]['lastmod']);
    }

    public function test_sitemap_builder_can_clear_items(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com', now()->toAtomString(), '0.8', 'daily');
        $builder->clear();

        $this->assertCount(0, $builder->getItems());
        $this->assertCount(0, $builder->getSitemaps());
    }

    public function test_sitemap_builder_can_add_sitemaps(): void
    {
        $builder = new SitemapBuilder;
        $builder->addSitemap('https://example.com/sitemap-pages.xml');

        $this->assertCount(1, $builder->getSitemaps());
    }

    public function test_sitemap_builder_renders_xml(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com', now()->toAtomString(), '0.8', 'daily');

        $xml = $builder->render();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('https://example.com', $xml);
    }

    public function test_sitemap_builder_generates_file(): void
    {
        $path = public_path('sitemap.xml');

        if (file_exists($path)) {
            unlink($path);
        }

        $builder = new SitemapBuilder;
        $builder->add('https://example.com');
        $builder->generate();

        $this->assertFileExists($path);
    }

    // ─────────────────────────────────────────────
    // Sitemap index
    // ─────────────────────────────────────────────

    public function test_sitemap_index_renders_correctly(): void
    {
        $builder = new SitemapBuilder;
        $builder->addSitemap('https://example.com/sitemap-pages.xml');
        $builder->addSitemap('https://example.com/sitemap-posts.xml');

        $xml = $builder->render('index');

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<sitemapindex', $xml);
        $this->assertStringContainsString('sitemap-pages.xml', $xml);
        $this->assertStringContainsString('sitemap-posts.xml', $xml);
    }

    // ─────────────────────────────────────────────
    // Facade
    // ─────────────────────────────────────────────

    public function test_sitemap_facade_works(): void
    {
        Sitemap::clear();
        Sitemap::add('https://example.com', now()->toAtomString(), '0.8', 'daily');

        $this->assertCount(1, Sitemap::getItems());

        Sitemap::clear();
    }

    // ─────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────

    public function test_helper_validates_priority(): void
    {
        $this->assertTrue(SitemapHelper::isValidPriority('0.0'));
        $this->assertTrue(SitemapHelper::isValidPriority('0.5'));
        $this->assertTrue(SitemapHelper::isValidPriority('1.0'));
        $this->assertFalse(SitemapHelper::isValidPriority('1.1'));
        $this->assertFalse(SitemapHelper::isValidPriority('-0.1'));
    }

    public function test_helper_validates_changefreq(): void
    {
        $this->assertTrue(SitemapHelper::isValidChangeFrequency('daily'));
        $this->assertTrue(SitemapHelper::isValidChangeFrequency('weekly'));
        $this->assertFalse(SitemapHelper::isValidChangeFrequency('never-ever'));
    }

    public function test_helper_formats_priority(): void
    {
        $this->assertSame('0.8', SitemapHelper::formatPriority('0.8'));
        $this->assertSame('1.0', SitemapHelper::formatPriority('1'));
        $this->assertSame('0.5', SitemapHelper::formatPriority('0.5'));
    }

    // ─────────────────────────────────────────────
    // Commands
    // ─────────────────────────────────────────────

    public function test_generate_command_creates_file(): void
    {
        $path = public_path('sitemap.xml');

        if (file_exists($path)) {
            unlink($path);
        }

        $this->artisan('sitemap:generate')->assertSuccessful();

        $this->assertFileExists($path);
    }

    public function test_generate_command_outputs_total_urls(): void
    {
        $this->artisan('sitemap:generate')
            ->assertSuccessful()
            ->expectsOutputToContain('Total URLs:');
    }

    public function test_generate_command_accepts_ping_flag(): void
    {
        $this->artisan('sitemap:generate', ['--ping' => true])
            ->assertSuccessful();
    }

    // ─────────────────────────────────────────────
    // Builder — addAlternate / hreflang
    // ─────────────────────────────────────────────

    public function test_builder_add_alternate_renders_xhtml_link(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com/en/about', null, '0.8', 'monthly');
        $builder->addAlternate('https://example.com/en/about', 'en', 'https://example.com/en/about');
        $builder->addAlternate('https://example.com/en/about', 'es', 'https://example.com/es/acerca');

        $xml = $builder->render();

        $this->assertStringContainsString('xhtml:link', $xml);
        $this->assertStringContainsString('hreflang="en"', $xml);
        $this->assertStringContainsString('hreflang="es"', $xml);
        $this->assertStringContainsString('https://example.com/es/acerca', $xml);
    }

    public function test_builder_add_alternate_adds_xmlns_xhtml_namespace(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com/', null);
        $builder->addAlternate('https://example.com/', 'en', 'https://example.com/en/');

        $xml = $builder->render();

        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $xml);
    }

    public function test_builder_without_alternates_has_no_xhtml_namespace(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com/', null);

        $xml = $builder->render();

        $this->assertStringNotContainsString('xmlns:xhtml', $xml);
    }

    public function test_builder_get_alternates_returns_stored_alternates(): void
    {
        $builder = new SitemapBuilder;
        $builder->addAlternate('https://example.com/', 'en', 'https://example.com/en/');
        $builder->addAlternate('https://example.com/', 'es', 'https://example.com/es/');

        $alternates = $builder->getAlternates();

        $this->assertCount(2, $alternates['https://example.com/']);
        $this->assertEquals('en', $alternates['https://example.com/'][0]['hreflang']);
    }

    public function test_builder_clear_resets_alternates(): void
    {
        $builder = new SitemapBuilder;
        $builder->addAlternate('https://example.com/', 'en', 'https://example.com/en/');
        $builder->clear();

        $this->assertEmpty($builder->getAlternates());
    }

    // ─────────────────────────────────────────────
    // Builder — addModel / chunkSitemapItems
    // ─────────────────────────────────────────────

    public function test_chunk_sitemap_items_method_exists_on_trait(): void
    {
        $this->assertTrue(
            method_exists(HasSitemapItems::class, 'chunkSitemapItems')
        );
    }

    public function test_add_model_uses_chunk_when_method_available(): void
    {
        $model = new class
        {
            public static function chunkSitemapItems(int $chunkSize, callable $callback): void
            {
                $item1 = new \stdClass;
                $item1->url = 'https://example.com/chunk-1';
                $item1->updated_at = null;
                $item1->sitemap_priority = '0.7';
                $item1->sitemap_changefreq = 'daily';

                $item2 = new \stdClass;
                $item2->url = 'https://example.com/chunk-2';
                $item2->updated_at = null;
                $item2->sitemap_priority = '0.6';
                $item2->sitemap_changefreq = 'weekly';

                $callback($item1);
                $callback($item2);
            }
        };

        $builder = new SitemapBuilder;
        $builder->addModel(get_class($model));

        $items = $builder->getItems();

        $this->assertCount(2, $items);
        $this->assertEquals('https://example.com/chunk-1', $items[0]['loc']);
        $this->assertEquals('https://example.com/chunk-2', $items[1]['loc']);
    }

    // ─────────────────────────────────────────────
    // ValidateSitemapCommand
    // ─────────────────────────────────────────────

    public function test_validate_command_fails_when_no_sitemap_file(): void
    {
        @unlink(public_path('sitemap.xml'));

        $this->artisan('sitemap:validate')
            ->assertFailed()
            ->expectsOutputToContain('not found');
    }

    public function test_validate_command_passes_on_valid_xml(): void
    {
        $builder = new SitemapBuilder;
        $builder->add('https://example.com/', null, '1.0', 'daily');
        $builder->generate();

        $this->artisan('sitemap:validate')
            ->assertSuccessful()
            ->expectsOutputToContain('OK');

        @unlink(public_path('sitemap.xml'));
    }

    public function test_validate_command_fails_on_invalid_xml(): void
    {
        file_put_contents(public_path('sitemap.xml'), '<broken xml>no closing tag');

        $this->artisan('sitemap:validate')
            ->assertFailed()
            ->expectsOutputToContain('XML');

        @unlink(public_path('sitemap.xml'));
    }
}
