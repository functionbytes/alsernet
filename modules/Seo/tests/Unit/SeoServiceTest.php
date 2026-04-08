<?php

namespace Modules\Seo\Tests\Unit;

use Modules\Seo\Services\SeoService;
use Tests\TestCase;

class SeoServiceTest extends TestCase
{
    private SeoService $seoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seoService = new SeoService;
    }

    public function test_render_includes_basic_meta_tags(): void
    {
        $this->seoService->setTitle('Test Page', false)
            ->setDescription('Test description for the page');

        $html = $this->seoService->render();

        $this->assertStringContainsString('<title>Test Page</title>', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('Test description for the page', $html);
    }

    public function test_render_includes_og_site_name(): void
    {
        $html = $this->seoService->render();
        $this->assertStringContainsString('og:site_name', $html);
    }

    public function test_render_includes_og_locale(): void
    {
        $html = $this->seoService->render();
        $this->assertStringContainsString('og:locale', $html);
    }

    public function test_render_includes_og_image_dimensions_when_image_set(): void
    {
        $this->seoService->setOgImage('https://example.com/image.jpg');
        $html = $this->seoService->render();

        $this->assertStringContainsString('og:image:width', $html);
        $this->assertStringContainsString('og:image:height', $html);
    }

    public function test_set_keywords_accepts_array(): void
    {
        $this->seoService->setKeywords(['laravel', 'php', 'seo']);
        $html = $this->seoService->render();
        $this->assertStringContainsString('laravel, php, seo', $html);
    }

    public function test_set_keywords_accepts_string(): void
    {
        $this->seoService->setKeywords('laravel, php');
        $html = $this->seoService->render();
        $this->assertStringContainsString('laravel, php', $html);
    }

    public function test_og_title_falls_back_to_title(): void
    {
        $this->seoService->setTitle('Fallback Title', false);
        $html = $this->seoService->render();
        // og:title should use the main title when og_title not set
        $this->assertStringContainsString('og:title', $html);
        $this->assertStringContainsString('Fallback Title', $html);
    }

    public function test_render_escapes_html_in_title(): void
    {
        $this->seoService->setTitle('<script>alert("xss")</script>', false);
        $html = $this->seoService->render();
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_canonical_url_is_rendered(): void
    {
        $this->seoService->setCanonical('https://example.com/test-page');
        $html = $this->seoService->render();
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('https://example.com/test-page', $html);
    }

    public function test_robots_noindex_is_rendered(): void
    {
        $this->seoService->setRobots('noindex,nofollow');
        $html = $this->seoService->render();
        $this->assertStringContainsString('noindex,nofollow', $html);
    }

    public function test_generate_preview_returns_correct_structure(): void
    {
        $this->seoService->setTitle('Preview Title', false)
            ->setDescription('Preview desc');

        $preview = $this->seoService->generatePreview();

        $this->assertArrayHasKey('google', $preview);
        $this->assertArrayHasKey('facebook', $preview);
        $this->assertArrayHasKey('twitter', $preview);
        $this->assertEquals('Preview Title', $preview['google']['title']);
    }

    public function test_fluent_interface_returns_self(): void
    {
        $result = $this->seoService->setTitle('Test', false)
            ->setDescription('Desc')
            ->setRobots('index,follow');

        $this->assertInstanceOf(SeoService::class, $result);
    }
}
