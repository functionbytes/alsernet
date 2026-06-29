<?php

namespace Modules\Template\Templates\Riode\Tests\Feature;

use Modules\Template\Templates\Riode\Tests\RiodeTestCase;

class RiodeMediaShortcodesTest extends RiodeTestCase
{
    // =========================================================================
    // [category-card]
    // =========================================================================

    /** @test */
    public function test_category_card_renders_with_required_attributes(): void
    {
        $result = shortcode('[category-card name="Hombre" href="/cat/hombre"]');

        $this->assertStringContainsString('category', $result);
        $this->assertStringContainsString('Hombre', $result);
        $this->assertStringContainsString('/cat/hombre', $result);
    }

    /** @test */
    public function test_category_card_returns_empty_without_required_attributes(): void
    {
        $result = shortcode('[category-card image="/img/cat.jpg"]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_category_card_renders_image_with_lazy_loading(): void
    {
        $result = shortcode('[category-card name="Mujer" href="/mujer" image="/img/mujer.jpg"]');

        $this->assertStringContainsString('loading="lazy"', $result);
        $this->assertStringContainsString('/img/mujer.jpg', $result);
    }

    /** @test */
    public function test_category_card_renders_icon_when_no_image(): void
    {
        $result = shortcode('[category-card name="Moda" href="/moda" icon="fas fa-tshirt" style="icon"]');

        $this->assertStringContainsString('fas fa-tshirt', $result);
        $this->assertStringContainsString('category-icon', $result);
    }

    /** @test */
    public function test_category_card_renders_count_and_count_label(): void
    {
        $result = shortcode('[category-card name="Accesorios" href="/acc" count="42" count-label="Artículos"]');

        $this->assertStringContainsString('42', $result);
        $this->assertStringContainsString('Artículos', $result);
        $this->assertStringContainsString('category-count', $result);
    }

    /** @test */
    public function test_category_card_zero_count_is_not_rendered(): void
    {
        $result = shortcode('[category-card name="Vacío" href="/vacio" count="0"]');

        $this->assertStringNotContainsString('category-count', $result);
    }

    /** @test */
    public function test_category_card_ellipse_style_adds_correct_class(): void
    {
        $result = shortcode('[category-card name="Niño" href="/nino" style="ellipse"]');

        $this->assertStringContainsString('category-ellipse', $result);
    }

    /** @test */
    public function test_category_card_bg_color_via_data_attribute_not_inline_style(): void
    {
        $result = shortcode('[category-card name="Tecnología" href="/tech" icon="fas fa-laptop" style="icon" bg-color="#4b5577"]');

        $this->assertStringContainsString('data-bg-color="#4b5577"', $result);
        $this->assertStringNotContainsString('style="', $result);
    }

    /** @test */
    public function test_category_card_invalid_hex_bg_color_is_ignored(): void
    {
        $result = shortcode('[category-card name="Test" href="/test" bg-color="not-a-hex"]');

        $this->assertStringNotContainsString('data-bg-color', $result);
    }

    // =========================================================================
    // [category-grid]
    // =========================================================================

    /** @test */
    public function test_category_grid_renders_with_children(): void
    {
        $result = shortcode('[category-grid cols="2"][category-card name="Hombre" href="/hombre"][category-card name="Mujer" href="/mujer"][/category-grid]');

        $this->assertStringContainsString('category-grid', $result);
        $this->assertStringContainsString('Hombre', $result);
        $this->assertStringContainsString('Mujer', $result);
    }

    /** @test */
    public function test_category_grid_returns_empty_with_no_children(): void
    {
        $result = shortcode('[category-grid cols="4"][/category-grid]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_category_grid_carousel_layout_renders_owl(): void
    {
        $inner = str_repeat('[category-card name="Cat" href="/cat"]', 3);
        $result = shortcode("[category-grid layout=\"carousel\" cols=\"3\"]{$inner}[/category-grid]");

        $this->assertStringContainsString('owl-carousel', $result);
        $this->assertStringContainsString('data-owl-options', $result);
    }

    // =========================================================================
    // [creative-grid] + [grid-item]
    // =========================================================================

    /** @test */
    public function test_creative_grid_renders_with_grid_item_children(): void
    {
        $result = shortcode('[creative-grid gutter="md"][grid-item cols="8" height="x2"]contenido[/grid-item][grid-item cols="4" height="x1"]otro[/grid-item][/creative-grid]');

        $this->assertStringContainsString('row grid', $result);
        $this->assertStringContainsString('grid-item', $result);
        $this->assertStringContainsString('col-lg-8', $result);
        $this->assertStringContainsString('col-lg-4', $result);
        $this->assertStringContainsString('height-x2', $result);
        $this->assertStringContainsString('height-x1', $result);
    }

    /** @test */
    public function test_creative_grid_returns_empty_without_content(): void
    {
        $result = shortcode('[creative-grid gutter="md"][/creative-grid]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_grid_item_renders_with_animation_data_attribute(): void
    {
        $result = shortcode('[grid-item cols="6" height="x2" animation="fadeInRight" delay=".3s"]contenido[/grid-item]');

        $this->assertStringContainsString('appear-animate', $result);
        $this->assertStringContainsString('data-animation-options', $result);
        $this->assertStringContainsString('fadeInRight', $result);
    }

    /** @test */
    public function test_grid_item_without_animation_has_no_data_attribute(): void
    {
        $result = shortcode('[grid-item cols="12" height="x1"]texto[/grid-item]');

        $this->assertStringNotContainsString('appear-animate', $result);
        $this->assertStringNotContainsString('data-animation-options', $result);
    }

    /** @test */
    public function test_grid_item_invalid_height_falls_back_to_x1(): void
    {
        $result = shortcode('[grid-item cols="6" height="invalid"]texto[/grid-item]');

        $this->assertStringContainsString('height-x1', $result);
    }

    // =========================================================================
    // [testimonial] + [testimonials]
    // =========================================================================

    /** @test */
    public function test_testimonial_renders_with_required_author(): void
    {
        $result = shortcode('[testimonial author="Ana García" role="Clienta" rating="5"]Excelente servicio[/testimonial]');

        $this->assertStringContainsString('Ana García', $result);
        $this->assertStringContainsString('Clienta', $result);
        $this->assertStringContainsString('Excelente servicio', $result);
    }

    /** @test */
    public function test_testimonial_returns_empty_without_author(): void
    {
        $result = shortcode('[testimonial rating="5"]Sin autor[/testimonial]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_testimonial_renders_star_rating(): void
    {
        $result = shortcode('[testimonial author="Luis" rating="4"]Muy bueno[/testimonial]');

        $this->assertStringContainsString('fa-star', $result);
        $this->assertStringContainsString('testimonial-rating', $result);
    }

    /** @test */
    public function test_testimonials_wrapper_renders_with_children(): void
    {
        $inner = '[testimonial author="Ana" rating="5"]Gran servicio[/testimonial]'
               .'[testimonial author="Luis" rating="4"]Muy bueno[/testimonial]';

        $result = shortcode("[testimonials layout=\"default\" cols=\"2\"]{$inner}[/testimonials]");

        $this->assertStringContainsString('testimonials-section', $result);
        $this->assertStringContainsString('Ana', $result);
        $this->assertStringContainsString('Luis', $result);
    }

    /** @test */
    public function test_testimonials_centered_border_layout_adds_correct_classes(): void
    {
        $inner = '[testimonial author="Ana" rating="5"]Texto[/testimonial]';
        $result = shortcode("[testimonials layout=\"centered-border\"]{$inner}[/testimonials]");

        $this->assertStringContainsString('testimonial-centered', $result);
        $this->assertStringContainsString('testimonial-border', $result);
    }

    /** @test */
    public function test_testimonials_with_title_renders_title(): void
    {
        $inner = '[testimonial author="Ana" rating="5"]Texto[/testimonial]';
        $result = shortcode("[testimonials title=\"Lo que dicen nuestros clientes\"]{$inner}[/testimonials]");

        $this->assertStringContainsString('testimonial-title', $result);
        $this->assertStringContainsString('Lo que dicen nuestros clientes', $result);
    }

    /** @test */
    public function test_testimonials_carousel_renders_owl_options(): void
    {
        $inner = str_repeat('[testimonial author="Ana" rating="5"]Texto[/testimonial]', 4);
        $result = shortcode("[testimonials carousel=\"true\" cols=\"3\"]{$inner}[/testimonials]");

        $this->assertStringContainsString('owl-carousel', $result);
        $this->assertStringContainsString('data-owl-options', $result);
    }

    /** @test */
    public function test_testimonials_centered_bg_without_image_falls_back(): void
    {
        // centered-bg without background-image should not add parallax class
        $inner = '[testimonial author="Ana" rating="5"]Texto[/testimonial]';
        $result = shortcode("[testimonials layout=\"centered-bg\"]{$inner}[/testimonials]");

        $this->assertStringNotContainsString('data-image-src', $result);
    }

    /** @test */
    public function test_testimonials_parallax_background_uses_data_attribute_not_inline_style(): void
    {
        $inner = '[testimonial author="Ana" rating="5"]Texto[/testimonial]';
        $result = shortcode("[testimonials layout=\"centered-bg\" background=\"parallax\" background-image=\"/img/bg.jpg\"]{$inner}[/testimonials]");

        $this->assertStringContainsString('data-image-src="/img/bg.jpg"', $result);
        $this->assertStringNotContainsString('style="background-image', $result);
    }
}
