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

    // ─── Button extended tests ─────────────────────────────────────────────────

    /** @test */
    public function button_legacy_class_attr_still_works()
    {
        $result = shortcode('[button url="/legacy" class="btn-danger"]Legacy[/button]');

        $this->assertStringContainsString('href="/legacy"', $result);
        $this->assertStringContainsString('btn btn-danger', $result);
        $this->assertStringContainsString('Legacy', $result);
    }

    /** @test */
    public function button_new_style_solid_with_color_and_shape()
    {
        $result = shortcode('[button url="/buy" style="solid" color="primary" shape="rounded" size="lg"]Buy[/button]');

        $this->assertStringContainsString('href="/buy"', $result);
        $this->assertStringContainsString('btn-primary', $result);
        $this->assertStringContainsString('btn-rounded', $result);
        $this->assertStringContainsString('btn-lg', $result);
        $this->assertStringContainsString('Buy', $result);
    }

    /** @test */
    public function button_outline_style_adds_correct_classes()
    {
        $result = shortcode('[button style="outline" color="dark" shape="ellipse"]Outline[/button]');

        $this->assertStringContainsString('btn-outline', $result);
        $this->assertStringContainsString('btn-dark', $result);
        $this->assertStringContainsString('btn-ellipse', $result);
    }

    /** @test */
    public function button_gradient_style_renders_correctly()
    {
        $result = shortcode('[button style="gradient" color="blue" shadow="lg"]Gradient[/button]');

        $this->assertStringContainsString('btn-gradient', $result);
        $this->assertStringContainsString('btn-blue', $result);
        $this->assertStringContainsString('btn-shadow-lg', $result);
    }

    /** @test */
    public function button_icon_right_position_renders_correctly()
    {
        $result = shortcode('[button style="solid" color="primary" icon="fas fa-arrow-right" icon-position="right"]Next[/button]');

        $this->assertStringContainsString('fas fa-arrow-right', $result);
        $this->assertStringContainsString('btn-icon-right', $result);
    }

    /** @test */
    public function button_icon_left_position_renders_correctly()
    {
        $result = shortcode('[button style="solid" color="success" icon="fas fa-check" icon-position="left"]Done[/button]');

        $this->assertStringContainsString('fas fa-check', $result);
        $this->assertStringContainsString('btn-icon-left', $result);
    }

    /** @test */
    public function button_disabled_adds_aria_attributes()
    {
        $result = shortcode('[button style="solid" color="primary" disabled="true"]Disabled[/button]');

        $this->assertStringContainsString('btn-disabled', $result);
        $this->assertStringContainsString('aria-disabled="true"', $result);
        $this->assertStringContainsString('tabindex="-1"', $result);
    }

    /** @test */
    public function button_blank_target_adds_rel_noopener()
    {
        $result = shortcode('[button url="/ext" target="_blank" style="solid" color="primary"]External[/button]');

        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    /** @test */
    public function button_block_size_adds_btn_block_class()
    {
        $result = shortcode('[button style="solid" color="primary" size="block"]Full Width[/button]');

        $this->assertStringContainsString('btn-block', $result);
    }

    /** @test */
    public function button_invalid_style_falls_back_to_solid()
    {
        $result = shortcode('[button style="invalid-style" color="primary"]Fallback[/button]');

        $this->assertStringContainsString('btn-primary', $result);
        $this->assertStringNotContainsString('btn-invalid-style', $result);
    }

    // ─── Alert extended tests ──────────────────────────────────────────────────

    /** @test */
    public function alert_default_type_and_style_render_correctly()
    {
        $result = shortcode('[alert]Default[/alert]');

        $this->assertStringContainsString('alert alert-primary', $result);
        $this->assertStringContainsString('alert-simple', $result);
        $this->assertStringContainsString('alert-inline', $result);
        $this->assertStringContainsString('role="alert"', $result);
    }

    /** @test */
    public function alert_style_light_layout_message_renders_correctly()
    {
        $result = shortcode('[alert type="warning" style="light" layout="message"]Warning message[/alert]');

        $this->assertStringContainsString('alert-warning', $result);
        $this->assertStringContainsString('alert-light', $result);
        $this->assertStringContainsString('alert-message', $result);
        $this->assertStringContainsString('Warning message', $result);
    }

    /** @test */
    public function alert_auto_icon_resolves_by_type()
    {
        $result = shortcode('[alert type="success" icon="auto"]Good[/alert]');

        $this->assertStringContainsString('fa-check-circle', $result);
        $this->assertStringContainsString('alert-icon', $result);
    }

    /** @test */
    public function alert_auto_icon_for_danger_type()
    {
        $result = shortcode('[alert type="danger" icon="auto"]Error[/alert]');

        $this->assertStringContainsString('fa-circle-exclamation', $result);
    }

    /** @test */
    public function alert_explicit_icon_overrides_auto()
    {
        $result = shortcode('[alert type="info" icon="fas fa-star"]Custom icon[/alert]');

        $this->assertStringContainsString('fas fa-star', $result);
        $this->assertStringContainsString('alert-icon', $result);
    }

    /** @test */
    public function alert_title_renders_as_h4()
    {
        $result = shortcode('[alert type="info" title="Notice"]Body text[/alert]');

        $this->assertStringContainsString('<h4 class="alert-title">Notice</h4>', $result);
        $this->assertStringContainsString('Body text', $result);
    }

    /** @test */
    public function alert_round_adds_alert_round_class()
    {
        $result = shortcode('[alert type="primary" round="true"]Round[/alert]');

        $this->assertStringContainsString('alert-round', $result);
    }

    /** @test */
    public function alert_button_text_renders_action_button()
    {
        $result = shortcode('[alert type="primary" button-text="Ver más" button-href="/info"]Action[/alert]');

        $this->assertStringContainsString('alert-btn', $result);
        $this->assertStringContainsString('Ver más', $result);
        $this->assertStringContainsString('href="/info"', $result);
    }

    /** @test */
    public function alert_dismissible_adds_close_button()
    {
        $result = shortcode('[alert type="success" dismissible="true"]Dismiss me[/alert]');

        $this->assertStringContainsString('btn-close', $result);
        $this->assertStringContainsString('data-bs-dismiss="alert"', $result);
        $this->assertStringContainsString('alert-dismissible', $result);
    }

    /** @test */
    public function alert_summary_layout_adds_correct_classes()
    {
        $result = shortcode('[alert type="danger" layout="summary"]<ul><li>Error 1</li></ul>[/alert]');

        $this->assertStringContainsString('alert-summary', $result);
        $this->assertStringContainsString('alert-message', $result);
        $this->assertStringContainsString('alert-inline', $result);
    }

    /** @test */
    public function alert_invalid_type_falls_back_to_primary()
    {
        $result = shortcode('[alert type="rainbow"]Fallback[/alert]');

        $this->assertStringContainsString('alert-primary', $result);
        $this->assertStringNotContainsString('alert-rainbow', $result);
    }

    // ─── Accordion extended tests ──────────────────────────────────────────────

    /** @test */
    public function accordion_default_style_is_boxed()
    {
        $result = shortcode('[accordion id="acc1"]Items[/accordion]');

        $this->assertStringContainsString('accordion-boxed', $result);
        $this->assertStringContainsString('accordion-plus', $result);
        $this->assertStringContainsString('id="acc1"', $result);
    }

    /** @test */
    public function accordion_simple_style_renders_correctly()
    {
        $result = shortcode('[accordion style="simple"]Items[/accordion]');

        $this->assertStringContainsString('accordion-simple', $result);
        $this->assertStringNotContainsString('accordion-boxed', $result);
    }

    /** @test */
    public function accordion_dropshadow_style_renders_correctly()
    {
        $result = shortcode('[accordion style="dropshadow" gutter="sm"]Items[/accordion]');

        $this->assertStringContainsString('accordion-dropshadow', $result);
        $this->assertStringContainsString('accordion-boxed', $result);
        $this->assertStringContainsString('accordion-gutter-sm', $result);
    }

    /** @test */
    public function accordion_background_style_adds_accordion_icon_class()
    {
        $result = shortcode('[accordion style="background" color="primary"]Items[/accordion]');

        $this->assertStringContainsString('accordion-background', $result);
        $this->assertStringContainsString('accordion-icon', $result);
        $this->assertStringContainsString('accordion-primary', $result);
    }

    /** @test */
    public function accordion_multi_open_attr_renders_in_data_attribute()
    {
        $result = shortcode('[accordion multi-open="true"]Items[/accordion]');

        $this->assertStringContainsString('data-multi-open="true"', $result);
    }

    /** @test */
    public function accordion_border_attrs_render_correctly()
    {
        $result = shortcode('[accordion style="boxed" border="true" card-border="true"]Items[/accordion]');

        $this->assertStringContainsString('accordion-border', $result);
        $this->assertStringContainsString('accordion-card-border', $result);
    }

    /** @test */
    public function accordion_icon_style_none_omits_plus_class()
    {
        $result = shortcode('[accordion icon-style="none"]Items[/accordion]');

        $this->assertStringNotContainsString('accordion-plus', $result);
    }

    /** @test */
    public function accordion_invalid_style_falls_back_to_boxed()
    {
        $result = shortcode('[accordion style="unknown"]Items[/accordion]');

        $this->assertStringContainsString('accordion-boxed', $result);
        $this->assertStringNotContainsString('accordion-unknown', $result);
    }

    /** @test */
    public function accordion_item_open_attr_marks_as_expanded()
    {
        $result = shortcode('[accordion-item title="Open Item" open="true"]Body[/accordion-item]');

        $this->assertStringContainsString('expanded', $result);
        $this->assertStringContainsString('aria-expanded="true"', $result);
        $this->assertStringContainsString('Open Item', $result);
        $this->assertStringContainsString('Body', $result);
    }

    /** @test */
    public function accordion_item_closed_by_default()
    {
        $result = shortcode('[accordion-item title="Closed Item"]Body[/accordion-item]');

        $this->assertStringContainsString('collapsed', $result);
        $this->assertStringContainsString('aria-expanded="false"', $result);
    }

    /** @test */
    public function accordion_item_legacy_show_attr_still_works()
    {
        $result = shortcode('[accordion-item title="Legacy" show="true"]Content[/accordion-item]');

        $this->assertStringContainsString('expanded', $result);
        $this->assertStringContainsString('aria-expanded="true"', $result);
    }

    /** @test */
    public function accordion_item_icon_renders_before_title()
    {
        $result = shortcode('[accordion-item title="Services" icon="far fa-star"]Content[/accordion-item]');

        $this->assertStringContainsString('far fa-star', $result);
        $this->assertStringContainsString('Services', $result);
        // Icon should appear before the title text in the output
        $iconPos = strpos($result, 'far fa-star');
        $titlePos = strpos($result, 'Services');
        $this->assertLessThan($titlePos, $iconPos);
    }

    /** @test */
    public function accordion_full_composition_renders_correctly()
    {
        $input = '[accordion style="dropshadow" gutter="md"]'
            .'[accordion-item title="FAQ 1" open="true"]Answer 1[/accordion-item]'
            .'[accordion-item title="FAQ 2" icon="fas fa-question"]Answer 2[/accordion-item]'
            .'[/accordion]';

        $result = shortcode($input);

        $this->assertStringContainsString('accordion-dropshadow', $result);
        $this->assertStringContainsString('FAQ 1', $result);
        $this->assertStringContainsString('FAQ 2', $result);
        $this->assertStringContainsString('fas fa-question', $result);
        $this->assertStringContainsString('Answer 1', $result);
        $this->assertStringContainsString('Answer 2', $result);
    }
}
