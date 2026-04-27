<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\CollapseWhitespace;
use Tests\TestCase;

class CollapseWhitespaceTest extends TestCase
{
    private CollapseWhitespace $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CollapseWhitespace;
    }

    public function test_collapses_multiple_spaces(): void
    {
        $this->assertSame('<p>hello world</p>', $this->middleware->apply('<p>hello   world</p>'));
    }

    public function test_removes_space_between_tags(): void
    {
        $this->assertSame('<div><span>', $this->middleware->apply('<div>   <span>'));
    }

    public function test_converts_crlf_to_lf(): void
    {
        $result = $this->middleware->apply("line1\r\nline2");
        $this->assertStringNotContainsString("\r", $result);
    }

    public function test_preserves_pre_content(): void
    {
        $html = '<pre>  indented   content  </pre>';
        $result = $this->middleware->apply($html);
        $this->assertStringContainsString('  indented   content  ', $result);
    }

    public function test_preserves_script_content(): void
    {
        $html = '<script>// single line comment'."\n".'var x = 1;</script>';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_preserves_textarea_content(): void
    {
        $html = '<textarea>  preserved   whitespace  </textarea>';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_preserves_style_content(): void
    {
        $html = '<style>.foo   {  color: red;  }</style>';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_collapses_whitespace_around_protected_blocks(): void
    {
        $html = "<p>  before  </p>\n   <pre>  keep  </pre>\n   <p>  after  </p>";
        $result = $this->middleware->apply($html);
        $this->assertStringContainsString('  keep  ', $result);
        $this->assertStringNotContainsString('<p>  before', $result);
    }

    public function test_handles_script_with_attributes(): void
    {
        $html = '<script type="text/javascript">var a = 1;'."\n".'var b = 2;</script>';
        $this->assertSame($html, $this->middleware->apply($html));
    }
}
