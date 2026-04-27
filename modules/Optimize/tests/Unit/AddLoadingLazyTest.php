<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\AddLoadingLazy;
use Tests\TestCase;

class AddLoadingLazyTest extends TestCase
{
    private AddLoadingLazy $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AddLoadingLazy;
    }

    public function test_adds_lazy_to_img(): void
    {
        $result = $this->middleware->apply('<img src="photo.jpg" alt="test">');
        $this->assertStringContainsString('loading="lazy"', $result);
    }

    public function test_does_not_duplicate_loading_attribute(): void
    {
        $html = '<img src="photo.jpg" loading="eager">';
        $result = $this->middleware->apply($html);
        $this->assertSame(1, substr_count($result, 'loading='));
        $this->assertStringNotContainsString('loading="lazy"', $result);
    }

    public function test_does_not_override_existing_loading_lazy(): void
    {
        $html = '<img src="photo.jpg" loading="lazy">';
        $result = $this->middleware->apply($html);
        $this->assertSame(1, substr_count($result, 'loading="lazy"'));
    }

    public function test_skips_img_with_fetchpriority_high(): void
    {
        $html = '<img src="hero.jpg" fetchpriority="high">';
        $result = $this->middleware->apply($html);
        $this->assertStringNotContainsString('loading="lazy"', $result);
    }

    public function test_adds_lazy_to_iframe(): void
    {
        $result = $this->middleware->apply('<iframe src="https://example.com/embed"></iframe>');
        $this->assertStringContainsString('loading="lazy"', $result);
    }

    public function test_does_not_add_lazy_to_iframe_without_src(): void
    {
        $html = '<iframe></iframe>';
        $result = $this->middleware->apply($html);
        $this->assertStringNotContainsString('loading="lazy"', $result);
    }

    public function test_does_not_add_lazy_to_img_without_src(): void
    {
        $html = '<img alt="no src">';
        $result = $this->middleware->apply($html);
        $this->assertStringNotContainsString('loading="lazy"', $result);
    }
}
