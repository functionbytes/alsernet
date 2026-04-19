<?php

namespace Modules\Shortcode\Tests\Feature;

use Tests\TestCase;

/**
 * Snapshots de HTML producido por los shortcodes default.
 * Un cambio de markup dispara estos tests para evitar regresiones visuales.
 */
class DefaultShortcodesSnapshotTest extends TestCase
{
    public function test_button_matches_snapshot(): void
    {
        $result = shortcode('[button url="/foo" class="primary" target="_blank"]Click[/button]');

        $this->assertEquals(
            '<a href="/foo" class="btn primary" target="_blank">Click</a>',
            $result
        );
    }

    public function test_alert_matches_snapshot(): void
    {
        $result = shortcode('[alert type="success"]OK[/alert]');

        $this->assertEquals(
            '<div class="alert alert-success" role="alert">OK</div>',
            $result
        );
    }

    public function test_alert_dismissible_matches_snapshot(): void
    {
        $result = shortcode('[alert type="warning" dismissible="true"]Cuidado[/alert]');

        $this->assertEquals(
            '<div class="alert alert-warning alert-dismissible fade show" role="alert">Cuidado<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>',
            $result
        );
    }

    public function test_columns_matches_snapshot(): void
    {
        $result = shortcode('[columns count="3" gap="4"]x[/columns]');

        $this->assertEquals(
            '<div class="row row-cols-1 row-cols-md-3 g-4">x</div>',
            $result
        );
    }

    public function test_youtube_matches_snapshot(): void
    {
        $result = shortcode('[youtube id="abc123" /]');

        $this->assertStringContainsString('<div class="ratio ratio-16x9">', $result);
        $this->assertStringContainsString('src="https://www.youtube.com/embed/abc123"', $result);
        $this->assertStringContainsString('allowfullscreen', $result);
    }

    public function test_badge_matches_snapshot(): void
    {
        $result = shortcode('[badge type="success"]Nuevo[/badge]');

        $this->assertEquals(
            '<span class="badge bg-success">Nuevo</span>',
            $result
        );
    }

    public function test_badge_pill_matches_snapshot(): void
    {
        $result = shortcode('[badge type="info" pill="true"]P[/badge]');

        $this->assertEquals(
            '<span class="badge bg-info rounded-pill">P</span>',
            $result
        );
    }

    public function test_quote_without_attribution_matches_snapshot(): void
    {
        $result = shortcode('[quote]Pienso luego existo[/quote]');

        $this->assertEquals(
            '<blockquote class="blockquote"><p>Pienso luego existo</p></blockquote>',
            $result
        );
    }

    public function test_current_year_matches_snapshot(): void
    {
        $result = shortcode('[current-year /]');

        $this->assertEquals((string) now()->year, $result);
    }
}
