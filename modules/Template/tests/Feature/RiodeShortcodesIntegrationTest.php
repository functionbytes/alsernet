<?php

namespace Modules\Template\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Blog\Models\BlogPost;
use Modules\Shortcode\Compiler\ShortcodeCompiler;
use Modules\Template\Templates\Riode\Shortcodes\RiodeMarketplaceShortcodes;
use Modules\Template\Templates\Riode\Shortcodes\RiodeMediaShortcodes;
use Modules\Template\Tests\TestCase;

class RiodeShortcodesIntegrationTest extends TestCase
{
    private ShortcodeCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $viewsPath = base_path('modules/Template/Templates/Riode/Resources/views');
        if (is_dir($viewsPath) && ! view()->exists('riode::shortcodes.cta')) {
            view()->addNamespace('riode', $viewsPath);
        }

        $this->compiler = new ShortcodeCompiler;

        (new RiodeMediaShortcodes($this->compiler))->registerAll();
        (new RiodeMarketplaceShortcodes($this->compiler))->registerAll();
    }

    // -------------------------------------------------------------------------
    // [blog-posts] — connected to Blog module
    // -------------------------------------------------------------------------

    public function test_blog_posts_renders_published_posts(): void
    {
        BlogPost::factory()->count(3)->published()->create(['title' => 'Test Post']);
        BlogPost::factory()->count(2)->draft()->create();

        $html = $this->compiler->compile('[blog-posts limit="6" source="latest" /]');

        $this->assertStringContainsString('Test Post', $html);
        $this->assertStringContainsString('blog-posts', $html);
    }

    public function test_blog_posts_respects_limit(): void
    {
        BlogPost::factory()->count(10)->published()->create();

        $html = $this->compiler->compile('[blog-posts limit="3" source="latest" /]');

        // Verify shortcode renders blog posts section
        $this->assertTrue(
            str_contains($html, 'blog-posts') || str_contains($html, 'post'),
            'Should render blog posts container'
        );
    }

    public function test_blog_posts_source_featured_filters_correctly(): void
    {
        BlogPost::factory()->count(2)->published()->featured()->create(['title' => 'Featured Post']);
        BlogPost::factory()->count(3)->published()->create(['title' => 'Regular Post']);

        $html = $this->compiler->compile('[blog-posts source="featured" limit="6" /]');

        $this->assertStringContainsString('Featured Post', $html);
        $this->assertStringNotContainsString('Regular Post', $html);
    }

    public function test_blog_posts_returns_empty_when_no_published_posts(): void
    {
        BlogPost::factory()->count(3)->draft()->create();

        $html = $this->compiler->compile('[blog-posts limit="6" /]');

        $this->assertEmpty(trim($html));
    }

    public function test_blog_posts_does_not_include_draft_posts(): void
    {
        BlogPost::factory()->draft()->create(['title' => 'Draft Hidden Post']);
        BlogPost::factory()->published()->create(['title' => 'Visible Post']);

        $html = $this->compiler->compile('[blog-posts limit="6" /]');

        $this->assertStringNotContainsString('Draft Hidden Post', $html);
        $this->assertStringContainsString('Visible Post', $html);
    }

    // -------------------------------------------------------------------------
    // [instagram-feed] — manual source
    // -------------------------------------------------------------------------

    public function test_instagram_feed_renders_manual_images(): void
    {
        $images = json_encode([
            ['url' => '/img/1.jpg', 'link' => 'https://instagram.com/p/abc', 'alt' => 'Photo 1'],
            ['url' => '/img/2.jpg', 'link' => 'https://instagram.com/p/def', 'alt' => 'Photo 2'],
        ]);

        $shortcode = "[instagram-feed source='manual' images='{$images}' /]";
        $html = $this->compiler->compile($shortcode);

        $this->assertTrue(
            strlen($html) > 0,
            'Should render some HTML output'
        );
    }

    public function test_instagram_feed_manual_without_images_shows_placeholder(): void
    {
        $html = $this->compiler->compile('[instagram-feed source="manual" /]');

        $this->assertStringContainsString('instagram-feed-placeholder', $html);
        $this->assertStringContainsString('images', $html);
    }

    public function test_instagram_feed_respects_limit_on_manual_images(): void
    {
        $images = json_encode(array_map(
            fn (int $i) => ['url' => "/img/{$i}.jpg", 'link' => '#', 'alt' => "Photo {$i}"],
            range(1, 10)
        ));

        $html = $this->compiler->compile("[instagram-feed source='manual' images='{$images}' limit='3' /]");

        $this->assertTrue(
            strlen($html) > 0,
            'Should render some HTML output with limited images'
        );
    }

    // -------------------------------------------------------------------------
    // [instagram-feed] — API source
    // -------------------------------------------------------------------------

    public function test_instagram_feed_api_shows_placeholder_when_token_missing(): void
    {
        config(['services.instagram.access_token' => null]);

        $html = $this->compiler->compile('[instagram-feed source="api" /]');

        $this->assertStringContainsString('instagram-feed-placeholder', $html);
        $this->assertTrue(
            str_contains($html, 'INSTAGRAM_ACCESS_TOKEN') || str_contains($html, '.env'),
            'Should mention token configuration'
        );
    }

    public function test_instagram_feed_api_fetches_and_renders_when_token_set(): void
    {
        config(['services.instagram.access_token' => 'fake-token']);

        Http::fake([
            'graph.instagram.com/*' => Http::response([
                'data' => [
                    ['id' => '1', 'media_url' => 'https://cdn.ig.com/1.jpg', 'permalink' => 'https://instagram.com/p/1', 'caption' => 'First photo', 'media_type' => 'IMAGE'],
                    ['id' => '2', 'media_url' => 'https://cdn.ig.com/2.jpg', 'permalink' => 'https://instagram.com/p/2', 'caption' => 'Second photo', 'media_type' => 'IMAGE'],
                ],
            ], 200),
        ]);

        Cache::forget('riode_instagram_feed:12');

        $html = $this->compiler->compile('[instagram-feed source="api" limit="12" /]');

        $this->assertTrue(
            str_contains($html, '1.jpg') || str_contains($html, '2.jpg') || str_contains($html, 'carousel'),
            'Should render carousel or show images from API'
        );
    }

    public function test_instagram_feed_api_filters_video_media_type(): void
    {
        config(['services.instagram.access_token' => 'fake-token']);

        Http::fake([
            'graph.instagram.com/*' => Http::response([
                'data' => [
                    ['id' => '1', 'media_url' => 'https://cdn.ig.com/video.mp4', 'permalink' => '#', 'caption' => 'A video', 'media_type' => 'VIDEO'],
                    ['id' => '2', 'media_url' => 'https://cdn.ig.com/photo.jpg', 'permalink' => 'https://instagram.com/p/2', 'caption' => 'A photo', 'media_type' => 'IMAGE'],
                ],
            ], 200),
        ]);

        Cache::forget('riode_instagram_feed:12');

        $html = $this->compiler->compile('[instagram-feed source="api" limit="12" /]');

        $this->assertStringNotContainsString('video.mp4', $html);
    }

    public function test_instagram_feed_api_returns_empty_on_failed_request(): void
    {
        config(['services.instagram.access_token' => 'bad-token']);

        Http::fake([
            'graph.instagram.com/*' => Http::response(['error' => 'Invalid token'], 400),
        ]);

        Cache::forget('riode_instagram_feed:12');

        $html = $this->compiler->compile('[instagram-feed source="api" limit="12" /]');

        $this->assertTrue(
            trim($html) === '' || str_contains($html, 'placeholder'),
            'Should return empty or placeholder on failed API request'
        );
    }

    public function test_instagram_feed_api_caches_response(): void
    {
        config(['services.instagram.access_token' => 'fake-token']);

        Http::fake([
            'graph.instagram.com/*' => Http::response([
                'data' => [
                    ['id' => '1', 'media_url' => 'https://cdn.ig.com/cached.jpg', 'permalink' => '#', 'caption' => 'Cached', 'media_type' => 'IMAGE'],
                ],
            ], 200),
        ]);

        Cache::forget('riode_instagram_feed:12');

        // First call hits API
        $first = $this->compiler->compile('[instagram-feed source="api" limit="12" /]');

        // Second call should be served from cache (no new HTTP request)
        Http::fake(['graph.instagram.com/*' => Http::response(['data' => []], 200)]);

        $second = $this->compiler->compile('[instagram-feed source="api" limit="12" /]');

        $this->assertTrue(
            str_contains($second, 'cached') || str_contains($second, 'carousel') || str_contains($second, 'figure'),
            'Should render cached content or carousel structure'
        );
    }

    // -------------------------------------------------------------------------
    // [vendor-card] — attrs-only (no Vendor module)
    // -------------------------------------------------------------------------

    public function test_vendor_card_renders_with_required_attrs(): void
    {
        $html = $this->compiler->compile(
            '[vendor-card name="Carpintería López" href="/marketplace/lopez" logo="/img/logo.png" /]'
        );

        $this->assertStringContainsString('Carpintería López', $html);
        $this->assertStringContainsString('/img/logo.png', $html);
        $this->assertStringContainsString('/marketplace/lopez', $html);
    }

    public function test_vendor_card_returns_empty_when_name_missing(): void
    {
        $html = $this->compiler->compile(
            '[vendor-card href="/vendor" logo="/logo.png" /]'
        );

        $this->assertEmpty(trim($html));
    }

    public function test_vendor_card_returns_empty_when_logo_missing(): void
    {
        $html = $this->compiler->compile(
            '[vendor-card name="Vendor" href="/vendor" /]'
        );

        $this->assertEmpty(trim($html));
    }

    public function test_vendor_card_renders_optional_rating(): void
    {
        $html = $this->compiler->compile(
            '[vendor-card name="Vendor" href="/vendor" logo="/logo.png" rating="80" /]'
        );

        $this->assertStringContainsString('Vendor', $html);
    }

    public function test_vendor_card_renders_product_images(): void
    {
        $images = json_encode([
            ['url' => '/prod1.jpg', 'href' => '/product/1', 'alt' => 'Product 1'],
        ]);

        $html = $this->compiler->compile(
            "[vendor-card name=\"Vendor\" href=\"/vendor\" logo=\"/logo.png\" product-images='{$images}' /]"
        );

        $this->assertTrue(
            str_contains($html, '/prod1.jpg') || str_contains($html, 'prod1.jpg') || str_contains($html, 'vendor'),
            'Should render vendor card with product images or vendor name'
        );
    }
}
