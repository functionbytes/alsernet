<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\RemoveComments;
use Tests\TestCase;

class RemoveCommentsTest extends TestCase
{
    private RemoveComments $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RemoveComments;
    }

    public function test_removes_simple_html_comment(): void
    {
        $this->assertSame('<div></div>', $this->middleware->apply('<div><!-- comment --></div>'));
    }

    public function test_removes_multiline_comment(): void
    {
        $html = "<!-- \n multi \n line \n --><p>text</p>";
        $this->assertSame('<p>text</p>', $this->middleware->apply($html));
    }

    public function test_preserves_ie_conditional_comments(): void
    {
        $html = '<!--[if IE]><link rel="stylesheet" href="ie.css"><![endif]-->';
        $result = $this->middleware->apply($html);
        $this->assertSame($html, $result);
    }

    public function test_removes_multiple_comments(): void
    {
        $html = '<!-- a --><p><!-- b -->text</p>';
        $result = $this->middleware->apply($html);
        $this->assertSame('<p>text</p>', $result);
    }

    public function test_leaves_non_comment_html_unchanged(): void
    {
        $html = '<div class="foo"><p>Hello</p></div>';
        $this->assertSame($html, $this->middleware->apply($html));
    }
}
