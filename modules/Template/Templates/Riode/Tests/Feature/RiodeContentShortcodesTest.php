<?php

namespace Modules\Template\Templates\Riode\Tests\Feature;

use Modules\Template\Templates\Riode\Tests\RiodeTestCase;

class RiodeContentShortcodesTest extends RiodeTestCase
{
    // =========================================================================
    // [cta]
    // =========================================================================

    /** @test */
    public function test_cta_renders_promo_layout_with_title_and_button(): void
    {
        $result = shortcode('[cta layout="promo" title="Trade-in" subtitle="Upgrade y ahorra" button-text="Comprar" button-href="/shop"]');

        $this->assertStringContainsString('banner', $result);
        $this->assertStringContainsString('cta-simple', $result);
        $this->assertStringContainsString('Trade-in', $result);
        $this->assertStringContainsString('Upgrade y ahorra', $result);
        $this->assertStringContainsString('Comprar', $result);
        $this->assertStringContainsString('/shop', $result);
    }

    /** @test */
    public function test_cta_defaults_to_promo_layout_when_invalid_layout_given(): void
    {
        $result = shortcode('[cta layout="nonexistent" title="Test"]');

        $this->assertStringContainsString('cta-simple', $result);
        $this->assertStringContainsString('Test', $result);
    }

    /** @test */
    public function test_cta_subscribe_layout_renders_form(): void
    {
        $result = shortcode('[cta layout="subscribe" title="Newsletter" form-action="/newsletter" form-button="Suscribirme"]');

        $this->assertStringContainsString('banner-newsletter', $result);
        $this->assertStringContainsString('<form', $result);
        $this->assertStringContainsString('/newsletter', $result);
        $this->assertStringContainsString('Suscribirme', $result);
    }

    /** @test */
    public function test_cta_subscribe_without_form_action_does_not_render_form(): void
    {
        $result = shortcode('[cta layout="subscribe" title="Newsletter"]');

        $this->assertStringNotContainsString('<form', $result);
    }

    /** @test */
    public function test_cta_3_cols_layout_renders_children(): void
    {
        $result = shortcode('[cta layout="3-cols"][cta-column image="/c1.jpg" title="Hombre" subtitle="Desde 29€" button-text="Ver"][/cta]');

        $this->assertStringContainsString('banner-group', $result);
        $this->assertStringContainsString('Hombre', $result);
        $this->assertStringContainsString('Desde 29€', $result);
    }

    /** @test */
    public function test_cta_parallax_layout_renders_without_social_by_default(): void
    {
        $result = shortcode('[cta layout="parallax" title="Sign up" background-image="/parallax.jpg"]');

        $this->assertStringContainsString('banner-background', $result);
        $this->assertStringContainsString('parallax', $result);
    }

    // =========================================================================
    // [countdown]
    // =========================================================================

    /** @test */
    public function test_countdown_renders_with_future_date(): void
    {
        $future = date('Y-m-d H:i:s', time() + 86400); // tomorrow
        $result = shortcode("[countdown until=\"{$future}\" layout=\"block\"]");

        $this->assertStringContainsString('countdown', $result);
        $this->assertStringContainsString('data-seconds', $result);
        $this->assertStringContainsString('data-format', $result);
    }

    /** @test */
    public function test_countdown_returns_empty_for_past_date(): void
    {
        $past = '2000-01-01 00:00:00';
        $result = shortcode("[countdown until=\"{$past}\" /]");

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_countdown_returns_empty_without_until_attribute(): void
    {
        $result = shortcode('[countdown layout="block" /]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_countdown_inline_banner_layout_renders_title_and_button(): void
    {
        $future = date('Y-m-d H:i:s', time() + 7200);
        $result = shortcode("[countdown until=\"{$future}\" layout=\"inline-banner\" title=\"Black Friday\" button-text=\"Ver ofertas\" button-href=\"/bf\"]");

        $this->assertStringContainsString('countdown-type1', $result);
        $this->assertStringContainsString('Black Friday', $result);
        $this->assertStringContainsString('Ver ofertas', $result);
        $this->assertStringContainsString('/bf', $result);
    }

    /** @test */
    public function test_countdown_accepts_unix_timestamp(): void
    {
        $ts = time() + 3600;
        $result = shortcode("[countdown until=\"{$ts}\" /]");

        $this->assertStringContainsString('countdown', $result);
        $this->assertStringContainsString('data-seconds', $result);
    }

    // =========================================================================
    // [counter]
    // =========================================================================

    /** @test */
    public function test_counter_renders_with_required_attributes(): void
    {
        $result = shortcode('[counter to="9500" title="Clientes felices" /]');

        $this->assertStringContainsString('counter-part', $result);
        $this->assertStringContainsString('count-to', $result);
        $this->assertStringContainsString('data-tovalue="9500"', $result);
        $this->assertStringContainsString('Clientes felices', $result);
    }

    /** @test */
    public function test_counter_returns_empty_without_to_attribute(): void
    {
        $result = shortcode('[counter title="Clientes" /]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_counter_renders_suffix_and_prefix(): void
    {
        $result = shortcode('[counter to="98" title="Satisfacción" prefix="+" suffix="%"]');

        $this->assertStringContainsString('symbol-percent', $result);
        $this->assertStringContainsString('data-tovalue="98"', $result);
    }

    /** @test */
    public function test_counter_renders_fa_icon(): void
    {
        $result = shortcode('[counter to="200" title="Proyectos" icon="fas fa-briefcase" /]');

        $this->assertStringContainsString('fas fa-briefcase', $result);
        $this->assertStringContainsString('counter-icon', $result);
    }

    /** @test */
    public function test_counter_renders_description(): void
    {
        $result = shortcode('[counter to="15" title="Años" description="De experiencia consolidada" /]');

        $this->assertStringContainsString('counter-descri', $result);
        $this->assertStringContainsString('De experiencia consolidada', $result);
    }

    // =========================================================================
    // [counter-grid]
    // =========================================================================

    /** @test */
    public function test_counter_grid_renders_with_child_counters(): void
    {
        $result = shortcode('[counter-grid layout="boxed" cols="3"][counter to="100" title="A" /][counter to="200" title="B" /][/counter-grid]');

        $this->assertStringContainsString('counter-grid', $result);
        $this->assertStringContainsString('counter-box', $result);
        $this->assertStringContainsString('col-lg-4', $result);
    }

    /** @test */
    public function test_counter_grid_returns_empty_with_no_children(): void
    {
        $result = shortcode('[counter-grid layout="boxed"][/counter-grid]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_counter_grid_simple_layout_uses_counter_simple_class(): void
    {
        $result = shortcode('[counter-grid layout="simple" cols="2"][counter to="50" title="X" /][/counter-grid]');

        $this->assertStringContainsString('counter-simple', $result);
        $this->assertStringContainsString('col-lg-6', $result);
    }

    // =========================================================================
    // [icon-box]
    // =========================================================================

    /** @test */
    public function test_icon_box_renders_with_required_attributes(): void
    {
        $result = shortcode('[icon-box icon="fas fa-truck" title="Envío gratis" /]');

        $this->assertStringContainsString('icon-box', $result);
        $this->assertStringContainsString('fas fa-truck', $result);
        $this->assertStringContainsString('Envío gratis', $result);
        $this->assertStringContainsString('aria-hidden="true"', $result);
    }

    /** @test */
    public function test_icon_box_returns_empty_without_icon(): void
    {
        $result = shortcode('[icon-box title="Sin icono" /]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_icon_box_returns_empty_without_title(): void
    {
        $result = shortcode('[icon-box icon="fas fa-star" /]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_icon_box_with_href_renders_anchor_tag(): void
    {
        $result = shortcode('[icon-box icon="fas fa-headset" title="Soporte" href="/soporte"]');

        $this->assertStringContainsString('<a ', $result);
        $this->assertStringContainsString('href="/soporte"', $result);
    }

    /** @test */
    public function test_icon_box_without_href_renders_div_tag(): void
    {
        $result = shortcode('[icon-box icon="fas fa-headset" title="Soporte" /]');

        $this->assertStringContainsString('<div ', $result);
        $this->assertStringNotContainsString('<a ', $result);
    }

    /** @test */
    public function test_icon_box_side_layout_adds_correct_class(): void
    {
        $result = shortcode('[icon-box icon="fas fa-shield-alt" title="Pago seguro" layout="side" style="inversed" /]');

        $this->assertStringContainsString('icon-box-side', $result);
        $this->assertStringContainsString('icon-inversed', $result);
    }

    // =========================================================================
    // [icon-box-grid]
    // =========================================================================

    /** @test */
    public function test_icon_box_grid_renders_with_children(): void
    {
        $result = shortcode('[icon-box-grid cols="3"][icon-box icon="fas fa-truck" title="Envío" /][icon-box icon="fas fa-lock" title="Seguro" /][/icon-box-grid]');

        $this->assertStringContainsString('icon-box-grid', $result);
        $this->assertStringContainsString('col-lg-4', $result);
        $this->assertStringContainsString('Envío', $result);
        $this->assertStringContainsString('Seguro', $result);
    }

    /** @test */
    public function test_icon_box_grid_returns_empty_with_no_children(): void
    {
        $result = shortcode('[icon-box-grid cols="4"][/icon-box-grid]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_icon_box_grid_4_cols_uses_col_lg_3(): void
    {
        $result = shortcode('[icon-box-grid cols="4"][icon-box icon="fas fa-star" title="A" /][/icon-box-grid]');

        $this->assertStringContainsString('col-lg-3', $result);
    }

    /** @test */
    public function test_icon_box_grid_carousel_layout_renders_owl_carousel(): void
    {
        $inner = str_repeat('[icon-box icon="fas fa-star" title="Item" /]', 3);
        $result = shortcode("[icon-box-grid layout=\"carousel\" cols=\"3\"]{$inner}[/icon-box-grid]");

        $this->assertStringContainsString('owl-carousel', $result);
        $this->assertStringContainsString('data-owl-options', $result);
    }

    /** @test */
    public function test_icon_box_grid_degrades_carousel_to_grid_with_single_child(): void
    {
        $result = shortcode('[icon-box-grid layout="carousel" cols="3"][icon-box icon="fas fa-star" title="Solo uno" /][/icon-box-grid]');

        // Should degrade to grid (no owl-carousel wrapper)
        $this->assertStringNotContainsString('owl-carousel', $result);
        $this->assertStringContainsString('icon-box-grid', $result);
    }
}
