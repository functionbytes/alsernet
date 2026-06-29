<?php

namespace Modules\Template\Templates\Riode\Tests\Feature;

use Modules\Template\Templates\Riode\Tests\RiodeTestCase;

class RiodeEffectsShortcodesTest extends RiodeTestCase
{
    // =========================================================================
    // [animate]
    // =========================================================================

    /** @test */
    public function test_animate_renders_appear_animate_wrapper_by_default(): void
    {
        $result = shortcode('[animate name="fadeInUpShorter"]Contenido[/animate]');

        $this->assertStringContainsString('appear-animate', $result);
        $this->assertStringContainsString('data-animation-options', $result);
        $this->assertStringContainsString('fadeInUpShorter', $result);
        $this->assertStringContainsString('Contenido', $result);
    }

    /** @test */
    public function test_animate_defaults_to_fade_in_for_invalid_name(): void
    {
        $result = shortcode('[animate name="nonExistentAnim"]Texto[/animate]');

        $this->assertStringContainsString('fadeIn', $result);
        $this->assertStringContainsString('Texto', $result);
    }

    /** @test */
    public function test_animate_returns_empty_with_no_content(): void
    {
        $result = shortcode('[animate name="fadeIn"][/animate]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_animate_slide_mode_renders_slide_animate_class(): void
    {
        $result = shortcode('[animate name="blurIn" slide-mode="true"]Slide content[/animate]');

        $this->assertStringContainsString('slide-animate', $result);
        $this->assertStringNotContainsString('appear-animate', $result);
        $this->assertStringContainsString('Slide content', $result);
    }

    /** @test */
    public function test_animate_embeds_duration_and_delay_in_data_options(): void
    {
        $result = shortcode('[animate name="zoomInUp" duration="1.5s" delay=".8s"]Item[/animate]');

        $this->assertStringContainsString('1.5s', $result);
        $this->assertStringContainsString('.8s', $result);
        $this->assertStringContainsString('zoomInUp', $result);
    }

    // =========================================================================
    // [floating]
    // =========================================================================

    /** @test */
    public function test_floating_renders_wrapper_and_layer_elements(): void
    {
        $result = shortcode('[floating depth="2"]<img src="/img/leaf.png" alt="hoja">[/floating]');

        $this->assertStringContainsString('class="floating"', $result);
        $this->assertStringContainsString('class="layer"', $result);
        $this->assertStringContainsString('data-options', $result);
        $this->assertStringContainsString('data-floating-depth', $result);
    }

    /** @test */
    public function test_floating_returns_empty_with_no_content(): void
    {
        $result = shortcode('[floating depth="1"][/floating]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_floating_depth_is_clamped_between_0_1_and_5(): void
    {
        $resultLow = shortcode('[floating depth="-5"]Texto[/floating]');
        $resultHigh = shortcode('[floating depth="99"]Texto[/floating]');

        $this->assertStringContainsString('data-floating-depth="0.1"', $resultLow);
        $this->assertStringContainsString('data-floating-depth="5"', $resultHigh);
    }

    /** @test */
    public function test_floating_invert_flags_are_encoded_in_data_options(): void
    {
        $result = shortcode('[floating invert-x="false" invert-y="true" depth="1"]Content[/floating]');

        $this->assertStringContainsString('"invertX"', $result);
        $this->assertStringContainsString('"invertY"', $result);
    }

    // =========================================================================
    // [scroll-reveal]
    // =========================================================================

    /** @test */
    public function test_scroll_reveal_renders_with_default_speed_and_direction(): void
    {
        $result = shortcode('[scroll-reveal]Texto parallax[/scroll-reveal]');

        $this->assertStringContainsString('scroll-reveal-el', $result);
        $this->assertStringContainsString('data-scroll-options', $result);
        $this->assertStringContainsString('Texto parallax', $result);
    }

    /** @test */
    public function test_scroll_reveal_slow_speed_adds_scroll_slow_class(): void
    {
        $result = shortcode('[scroll-reveal speed="slow" direction="up"]Slow content[/scroll-reveal]');

        $this->assertStringContainsString('scroll-slow', $result);
        $this->assertStringContainsString('Slow content', $result);
    }

    /** @test */
    public function test_scroll_reveal_entire_speed_adds_scroll_entire_class(): void
    {
        $result = shortcode('[scroll-reveal speed="entire"]Hero[/scroll-reveal]');

        $this->assertStringContainsString('scroll-entire', $result);
    }

    /** @test */
    public function test_scroll_reveal_returns_empty_with_no_content(): void
    {
        $result = shortcode('[scroll-reveal speed="fast"][/scroll-reveal]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_scroll_reveal_from_to_overrides_are_embedded(): void
    {
        $result = shortcode('[scroll-reveal from="translateX(-50%)" to="translateX(50%)"]Custom[/scroll-reveal]');

        $this->assertStringContainsString('translateX(-50%)', $result);
        $this->assertStringContainsString('translateX(50%)', $result);
    }

    // =========================================================================
    // [svg-float]
    // =========================================================================

    /** @test */
    public function test_svg_float_renders_svg_element_with_path(): void
    {
        $path = 'M0,70V0h70v70H0z';
        $result = shortcode("[svg-float svg-path=\"{$path}\" delta=\"1\" speed=\"2\" /]");

        $this->assertStringContainsString('svg-template', $result);
        $this->assertStringContainsString('float-svg', $result);
        $this->assertStringContainsString('data-float-options', $result);
        $this->assertStringContainsString($path, $result);
    }

    /** @test */
    public function test_svg_float_returns_empty_without_svg_path(): void
    {
        $result = shortcode('[svg-float delta="2" /]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_svg_float_renders_background_and_overlay_images(): void
    {
        $result = shortcode('[svg-float svg-path="M0,0h70v70H0z" background="/bg.jpg" image="/overlay.png" /]');

        $this->assertStringContainsString('svg-background', $result);
        $this->assertStringContainsString('/bg.jpg', $result);
        $this->assertStringContainsString('/overlay.png', $result);
    }

    /** @test */
    public function test_svg_float_delta_is_clamped_to_valid_range(): void
    {
        $result = shortcode('[svg-float svg-path="M0,0h70v70H0z" delta="99" /]');

        $this->assertStringContainsString('"delta":3', $result);
    }

    /** @test */
    public function test_svg_float_renders_notice_image_with_svg_notice_class(): void
    {
        $result = shortcode('[svg-float svg-path="M0,0h70v70H0z" notice-image="/badge.png" /]');

        $this->assertStringContainsString('svg-notice', $result);
        $this->assertStringContainsString('/badge.png', $result);
    }
}
