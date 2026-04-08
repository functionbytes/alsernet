<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\ElideAttributes;
use Tests\TestCase;

class ElideAttributesTest extends TestCase
{
    private ElideAttributes $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ElideAttributes;
    }

    public function test_removes_method_get_quoted(): void
    {
        $this->assertSame('<form>', $this->middleware->apply('<form method="get">'));
    }

    public function test_removes_method_get_unquoted(): void
    {
        $this->assertSame('<form>', $this->middleware->apply('<form method=get>'));
    }

    public function test_collapses_disabled_attribute(): void
    {
        $this->assertSame('<input disabled>', $this->middleware->apply('<input disabled="disabled">'));
    }

    public function test_collapses_selected_attribute(): void
    {
        $this->assertSame('<option selected>', $this->middleware->apply('<option selected="selected">'));
    }

    public function test_collapses_checked_attribute(): void
    {
        $this->assertSame('<input checked>', $this->middleware->apply('<input checked="checked">'));
    }

    public function test_collapses_readonly_attribute(): void
    {
        $this->assertSame('<input readonly>', $this->middleware->apply('<input readonly="readonly">'));
    }

    public function test_collapses_multiple_attribute(): void
    {
        $this->assertSame('<select multiple>', $this->middleware->apply('<select multiple="multiple">'));
    }

    public function test_preserves_method_post(): void
    {
        $html = '<form method="post">';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_preserves_unrelated_attributes(): void
    {
        $html = '<input type="text" name="foo">';
        $this->assertSame($html, $this->middleware->apply($html));
    }

    public function test_removes_type_text_javascript_from_script(): void
    {
        $this->assertSame(
            '<script src="/app.js">',
            $this->middleware->apply('<script type="text/javascript" src="/app.js">')
        );
    }

    public function test_removes_type_text_css_from_style(): void
    {
        $this->assertSame(
            '<style>',
            $this->middleware->apply('<style type="text/css">')
        );
    }

    public function test_preserves_non_default_script_type(): void
    {
        $html = '<script type="module" src="/app.js">';
        $this->assertSame($html, $this->middleware->apply($html));
    }
}
