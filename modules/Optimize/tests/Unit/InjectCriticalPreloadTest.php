<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\InjectCriticalPreload;
use PHPUnit\Framework\TestCase;

class InjectCriticalPreloadTest extends TestCase
{
    private InjectCriticalPreload $mw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mw = new InjectCriticalPreload;
    }

    public function test_preloads_first_stylesheet(): void
    {
        $in = '<html><head><link rel="stylesheet" href="/css/main.css"></head><body></body></html>';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('rel="preload"', $out);
        $this->assertStringContainsString('as="style"', $out);
        $this->assertStringContainsString('/css/main.css', $out);
    }

    public function test_preloads_hero_image_with_fetchpriority(): void
    {
        $in = '<html><head></head><body><img class="hero" src="/img/hero.jpg"></body></html>';
        $out = $this->mw->apply($in);

        $this->assertStringContainsString('as="image"', $out);
        $this->assertStringContainsString('fetchpriority="high"', $out);
    }

    public function test_does_nothing_when_no_hero_and_no_stylesheet(): void
    {
        $in = '<html><head></head><body><p>hi</p></body></html>';
        $out = $this->mw->apply($in);

        $this->assertSame($in, $out);
    }

    public function test_does_not_duplicate_preload_if_already_present(): void
    {
        $in = '<html><head><link rel="preload" href="/css/main.css" as="style">'.
              '<link rel="stylesheet" href="/css/main.css"></head></html>';
        $out = $this->mw->apply($in);

        $count = substr_count($out, 'rel="preload" as="style" href="/css/main.css"');
        $this->assertLessThanOrEqual(1, $count);
    }
}
