<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\DeferJavascript;
use Tests\TestCase;

class DeferJavascriptTest extends TestCase
{
    private DeferJavascript $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new DeferJavascript;
    }

    public function test_adds_defer_to_external_script(): void
    {
        $html = '<script src="/app.js"></script>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('defer', $result);
    }

    public function test_does_not_double_defer(): void
    {
        $html = '<script src="/app.js" defer></script>';
        $result = $this->middleware->apply($html);

        $this->assertSame(1, substr_count($result, 'defer'));
    }

    public function test_skips_async_scripts(): void
    {
        $html = '<script src="/app.js" async></script>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('defer', $result);
    }

    public function test_skips_pagespeed_no_defer(): void
    {
        $html = '<script src="/app.js" data-pagespeed-no-defer></script>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString(' defer', $result);
    }

    public function test_does_not_defer_inline_scripts(): void
    {
        $html = '<script>alert("inline")</script>';
        $result = $this->middleware->apply($html);

        $this->assertSame($html, $result);
    }

    public function test_skips_type_module_scripts(): void
    {
        $html = '<script type="module" src="/app.js"></script>';
        $result = $this->middleware->apply($html);
        $this->assertStringNotContainsString('defer', $result);
    }
}
