<?php

namespace Modules\Optimize\Tests\Unit;

use Modules\Optimize\Http\Middleware\RemoveQuotes;
use Tests\TestCase;

class RemoveQuotesTest extends TestCase
{
    private RemoveQuotes $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RemoveQuotes;
    }

    public function test_removes_quotes_from_safe_src(): void
    {
        $result = $this->middleware->apply('<img src="image.png">');
        $this->assertStringContainsString('src=image.png', $result);
    }

    public function test_removes_quotes_from_safe_width(): void
    {
        $result = $this->middleware->apply('<img src="a.png" width="100">');
        $this->assertStringContainsString('width=100', $result);
    }

    public function test_preserves_quotes_when_value_has_query_string(): void
    {
        $html = '<img src="/img?w=100&h=200">';
        $result = $this->middleware->apply($html);
        $this->assertSame($html, $result);
    }

    public function test_preserves_quotes_when_value_has_spaces(): void
    {
        $html = '<input name="first name">';
        $result = $this->middleware->apply($html);
        $this->assertSame($html, $result);
    }

    public function test_preserves_quotes_when_value_has_equals(): void
    {
        $html = '<input name="a=b">';
        $result = $this->middleware->apply($html);
        $this->assertSame($html, $result);
    }

    public function test_only_acts_on_allowed_tags(): void
    {
        $html = '<div src="foo.png">';
        $result = $this->middleware->apply($html);
        $this->assertSame($html, $result);
    }

    public function test_does_not_alter_unrelated_attributes(): void
    {
        $html = '<img src="a.png" alt="A photo">';
        $result = $this->middleware->apply($html);
        $this->assertStringContainsString('alt="A photo"', $result);
    }
}
