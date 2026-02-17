<?php

namespace Modules\Shortcode\Tests\Feature;

use Tests\TestCase;

class DefaultShortcodesTest extends TestCase
{
    /** @test */
    public function button_shortcode_renders_correctly()
    {
        $result = shortcode('[button url="/test" class="primary"]Click[/button]');

        $this->assertStringContainsString('href="/test"', $result);
        $this->assertStringContainsString('btn primary', $result);
        $this->assertStringContainsString('Click', $result);
    }

    /** @test */
    public function alert_shortcode_renders_correctly()
    {
        $result = shortcode('[alert type="success"]Message[/alert]');

        $this->assertStringContainsString('alert alert-success', $result);
        $this->assertStringContainsString('Message', $result);
    }

    /** @test */
    public function columns_shortcode_renders_correctly()
    {
        $result = shortcode('[columns count="3"]Content[/columns]');

        $this->assertStringContainsString('row row-cols-1 row-cols-md-3', $result);
        $this->assertStringContainsString('Content', $result);
    }

    /** @test */
    public function youtube_shortcode_renders_correctly()
    {
        $result = shortcode('[youtube id="abc123" /]');

        $this->assertStringContainsString('youtube.com/embed/abc123', $result);
        $this->assertStringContainsString('iframe', $result);
    }

    /** @test */
    public function icon_shortcode_renders_correctly()
    {
        $result = shortcode('[icon name="heart" size="24" color="danger" /]');

        $this->assertStringContainsString('bi bi-heart', $result);
        $this->assertStringContainsString('text-danger', $result);
        $this->assertStringContainsString('font-size: 24px', $result);
    }

    /** @test */
    public function badge_shortcode_renders_correctly()
    {
        $result = shortcode('[badge type="primary"]New[/badge]');

        $this->assertStringContainsString('badge bg-primary', $result);
        $this->assertStringContainsString('New', $result);
    }

    /** @test */
    public function card_shortcode_renders_correctly()
    {
        $result = shortcode('[card title="Test Card"]Content[/card]');

        $this->assertStringContainsString('card', $result);
        $this->assertStringContainsString('card-header', $result);
        $this->assertStringContainsString('Test Card', $result);
        $this->assertStringContainsString('Content', $result);
    }

    /** @test */
    public function quote_shortcode_renders_correctly()
    {
        $result = shortcode('[quote author="John Doe"]Quote text[/quote]');

        $this->assertStringContainsString('blockquote', $result);
        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('Quote text', $result);
    }

    /** @test */
    public function accordion_shortcode_renders_correctly()
    {
        $result = shortcode('[accordion id="test"]Items[/accordion]');

        $this->assertStringContainsString('accordion', $result);
        $this->assertStringContainsString('id="test"', $result);
    }

    /** @test */
    public function nested_shortcodes_work_correctly()
    {
        $result = shortcode('[card title="Test"]
            [button url="/test"]Click[/button]
        [/card]');

        $this->assertStringContainsString('card', $result);
        $this->assertStringContainsString('btn', $result);
        $this->assertStringContainsString('Click', $result);
    }
}
