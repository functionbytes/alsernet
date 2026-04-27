<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\InlineCss;
use Tests\TestCase;

class InlineCssTest extends TestCase
{
    private InlineCss $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new InlineCss;
    }

    public function test_moves_inline_style_to_head(): void
    {
        $html = '<html><head></head><body><p style="color:red"></p></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('color:red', $result);
        $this->assertStringNotContainsString('style="color:red"', $result);
    }

    public function test_reuses_class_for_duplicate_styles(): void
    {
        $html = '<html><head></head><body><p style="color:red"></p><span style="color:red"></span></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertSame(1, substr_count($result, 'color:red'));
    }

    public function test_generates_sequential_class_names(): void
    {
        $html = '<html><head></head><body><p style="color:red"></p><span style="font-weight:bold"></span></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('pso-0', $result);
        $this->assertStringContainsString('pso-1', $result);
    }

    public function test_merges_with_existing_class_attribute(): void
    {
        $html = '<html><head></head><body><p class="existing" style="color:red"></p></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('class="existing pso-0"', $result);
        $this->assertStringNotContainsString('class="existing" class=', $result);
    }

    public function test_escapes_style_close_tag(): void
    {
        $html = '<html><head></head><body><p style="color:red</style><script>alert(1)</script>"></p></body></html>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('</style><script>alert', $result);
    }

    public function test_returns_unchanged_when_no_inline_styles(): void
    {
        $html = '<html><head></head><body><p class="foo"></p></body></html>';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_resets_state_between_calls(): void
    {
        $html = '<html><head></head><body><p style="color:red"></p></body></html>';
        $this->middleware->apply($html);
        $result = $this->middleware->apply($html);

        // pso-0 appears in class="" attribute AND in the <style> block — exactly 2 per call
        $this->assertSame(2, substr_count($result, 'pso-0'));
    }

    public function test_handles_single_quoted_style(): void
    {
        $html = '<html><head></head><body><p style=\'color:red\'></p></body></html>';
        $result = $this->middleware->apply($html);
        $this->assertStringContainsString('<style>', $result);
        $this->assertStringNotContainsString("style='color:red'", $result);
    }
}
