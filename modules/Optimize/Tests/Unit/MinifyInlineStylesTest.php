<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\MinifyInlineStyles;
use Tests\TestCase;

class MinifyInlineStylesTest extends TestCase
{
    private MinifyInlineStyles $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new MinifyInlineStyles;
    }

    public function test_removes_css_comments(): void
    {
        $html = '<style>/* This is a comment */ body { color: red; }</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('This is a comment', $result);
        $this->assertStringContainsString('color:red', $result);
    }

    public function test_collapses_whitespace_inside_style_blocks(): void
    {
        $html = '<style>body   {   color:   red;   }</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString('   ', $result);
    }

    public function test_removes_spaces_around_braces_colons_semicolons(): void
    {
        $html = '<style>body { color : red ; margin : 0 ; }</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('body{', $result);
        $this->assertStringContainsString('color:red', $result);
        $this->assertStringContainsString('margin:0', $result);
    }

    public function test_removes_trailing_semicolons_before_closing_brace(): void
    {
        $html = '<style>body { color: red; margin: 0; }</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringNotContainsString(';}', $result);
        $this->assertStringContainsString('}', $result);
    }

    public function test_preserves_style_block_content(): void
    {
        $html = '<style>body { color: red; }</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('</style>', $result);
        $this->assertStringContainsString('color:red', $result);
    }

    public function test_leaves_html_outside_style_blocks_unchanged(): void
    {
        $html = '<p>  hello   world  </p><style>body{color:red}</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('<p>  hello   world  </p>', $result);
    }

    public function test_handles_style_tag_with_attributes(): void
    {
        $html = '<style type="text/css">/* comment */ body { color: red; }</style>';
        $result = $this->middleware->apply($html);

        $this->assertStringContainsString('<style type="text/css">', $result);
        $this->assertStringNotContainsString('comment', $result);
        $this->assertStringContainsString('color:red', $result);
    }
}
