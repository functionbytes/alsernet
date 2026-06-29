<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\MinifyInlineScripts;
use Tests\TestCase;

class MinifyInlineScriptsTest extends TestCase
{
    private MinifyInlineScripts $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new MinifyInlineScripts;
    }

    public function test_removes_block_comments(): void
    {
        $html = '<script>/* init */var x = 1;</script>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('/* init */', $result);
        $this->assertStringContainsString('var x = 1;', $result);
    }

    public function test_removes_multiline_block_comments(): void
    {
        $html = "<script>/*\n * comment\n */var y = 2;</script>";
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('comment', $result);
        $this->assertStringContainsString('var y = 2;', $result);
    }

    public function test_collapses_whitespace(): void
    {
        $html = "<script>var a = 1;\n\n   var b = 2;</script>";
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('  ', $result);
    }

    public function test_does_not_touch_external_scripts(): void
    {
        $html = '<script src="/app.js">/* should not be touched */</script>';
        $result = $this->middleware->apply($html);

        $this->assertSame($html, $result);
    }

    public function test_returns_unchanged_when_no_inline_scripts(): void
    {
        $html = '<p>no scripts here</p>';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_preserves_string_with_double_slash(): void
    {
        $html = '<script>var url = "https://example.com";</script>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('https://example.com', $result);
    }

    public function test_trims_script_content(): void
    {
        $html = "<script>\n  var x = 1;  \n</script>";
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('<script>var x = 1;</script>', $result);
    }
}
