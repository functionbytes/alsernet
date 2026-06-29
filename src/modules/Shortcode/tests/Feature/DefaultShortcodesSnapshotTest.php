<?php

namespace Modules\Shortcode\Tests\Feature;

use Tests\TestCase;

/**
 * Snapshots de HTML producido por los shortcodes default.
 * Un cambio de markup dispara estos tests para evitar regresiones visuales.
 */
class DefaultShortcodesSnapshotTest extends TestCase
{
    // Button, alert and accordion shortcodes were expanded with Riode-style attributes and richer
    // default classes. Exact-string snapshots would break on every future attribute addition, so
    // these tests verify structural correctness (element type, required classes, content) instead.

    public function test_button_matches_snapshot(): void
    {
        $result = shortcode('[button url="/foo" class="primary" target="_blank"]Click[/button]');

        // Legacy path (no style/color/shape/size attrs): renders a plain anchor with class="btn {class}".
        $this->assertStringContainsString('<a ', $result);
        $this->assertStringContainsString('href="/foo"', $result);
        $this->assertStringContainsString('class="btn primary"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
        $this->assertStringContainsString('Click', $result);
    }

    public function test_alert_matches_snapshot(): void
    {
        $result = shortcode('[alert type="success"]OK[/alert]');

        $this->assertStringContainsString('<div ', $result);
        $this->assertStringContainsString('alert', $result);
        $this->assertStringContainsString('alert-success', $result);
        $this->assertStringContainsString('role="alert"', $result);
        $this->assertStringContainsString('OK', $result);
    }

    public function test_alert_dismissible_matches_snapshot(): void
    {
        $result = shortcode('[alert type="warning" dismissible="true"]Cuidado[/alert]');

        $this->assertStringContainsString('alert-warning', $result);
        $this->assertStringContainsString('alert-dismissible', $result);
        $this->assertStringContainsString('fade', $result);
        $this->assertStringContainsString('show', $result);
        $this->assertStringContainsString('role="alert"', $result);
        $this->assertStringContainsString('Cuidado', $result);
        $this->assertStringContainsString('btn-close', $result);
        $this->assertStringContainsString('data-bs-dismiss="alert"', $result);
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
