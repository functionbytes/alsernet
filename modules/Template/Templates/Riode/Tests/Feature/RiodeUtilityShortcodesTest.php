<?php

namespace Modules\Template\Templates\Riode\Tests\Feature;

use Modules\Template\Templates\Riode\Tests\RiodeTestCase;

class RiodeUtilityShortcodesTest extends RiodeTestCase
{
    // =========================================================================
    // [breadcrumb]
    // =========================================================================

    public function test_breadcrumb_renders_with_valid_items(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Productos","url":"/productos"},{"label":"Detalle"}]\']');

        $this->assertStringContainsString('breadcrumb', $result);
        $this->assertStringContainsString('Inicio', $result);
        $this->assertStringContainsString('/productos', $result);
        $this->assertStringContainsString('Detalle', $result);
        $this->assertStringContainsString('delimiter', $result);
        $this->assertStringContainsString('aria-current="page"', $result);
    }

    public function test_breadcrumb_returns_empty_without_items(): void
    {
        $result = shortcode('[breadcrumb separator="chevron"]');

        $this->assertSame('', $result);
    }

    public function test_breadcrumb_chevron_separator_renders_correctly(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Blog"}]\' separator="chevron"]');

        $this->assertStringContainsString('delimiter', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    public function test_breadcrumb_dot_separator(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Blog"}]\' separator="dot"]');

        $this->assertStringContainsString('·', $result);
    }

    public function test_breadcrumb_dark_background_applies_dark_section_class(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Blog"}]\' background="dark"]');

        $this->assertStringContainsString('dark-section', $result);
        $this->assertStringNotContainsString('grey-section', $result);
    }

    public function test_breadcrumb_none_background_has_no_section_class(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Blog"}]\' background="none"]');

        $this->assertStringNotContainsString('dark-section', $result);
        $this->assertStringNotContainsString('grey-section', $result);
    }

    public function test_breadcrumb_show_home_icon_renders_fa_house(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Blog"}]\' show-home-icon="true"]');

        $this->assertStringContainsString('fas fa-house', $result);
    }

    public function test_breadcrumb_center_align_adds_justify_content_center(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Inicio","url":"/"},{"label":"Blog"}]\' align="center"]');

        $this->assertStringContainsString('justify-content-center', $result);
    }

    public function test_breadcrumb_single_item_renders_without_delimiter(): void
    {
        $result = shortcode('[breadcrumb items=\'[{"label":"Solo"}]\']');

        $this->assertStringContainsString('Solo', $result);
        $this->assertStringNotContainsString('delimiter', $result);
    }

    public function test_breadcrumb_returns_empty_with_invalid_json(): void
    {
        $result = shortcode('[breadcrumb items="not-valid-json"]');

        $this->assertSame('', $result);
    }

    // =========================================================================
    // [page-header]
    // =========================================================================

    public function test_page_header_renders_title(): void
    {
        $result = shortcode('[page-header title="Sobre nosotros"]');

        $this->assertStringContainsString('page-header', $result);
        $this->assertStringContainsString('page-title', $result);
        $this->assertStringContainsString('Sobre nosotros', $result);
    }

    public function test_page_header_returns_empty_without_title(): void
    {
        $result = shortcode('[page-header subtitle="Algo"]');

        $this->assertSame('', $result);
    }

    public function test_page_header_dark_background_applies_dark_section(): void
    {
        $result = shortcode('[page-header title="Contacto" background="dark"]');

        $this->assertStringContainsString('dark-section', $result);
    }

    public function test_page_header_grey_background_applies_grey_section(): void
    {
        $result = shortcode('[page-header title="Blog" background="grey" text-color="dark"]');

        $this->assertStringContainsString('grey-section', $result);
        $this->assertStringNotContainsString('text-white', $result);
    }

    public function test_page_header_image_background_renders_figure(): void
    {
        $result = shortcode('[page-header title="Hero" background="image" background-image="/img/hero.jpg"]');

        $this->assertStringContainsString('page-header-bg', $result);
        $this->assertStringContainsString('/img/hero.jpg', $result);
        $this->assertStringContainsString('data-bg-image', $result);
    }

    public function test_page_header_image_background_without_image_degrades_to_dark(): void
    {
        $result = shortcode('[page-header title="Hero" background="image"]');

        $this->assertStringContainsString('dark-section', $result);
        $this->assertStringNotContainsString('page-header-bg', $result);
    }

    public function test_page_header_renders_breadcrumb_when_provided(): void
    {
        $result = shortcode('[page-header title="Productos" breadcrumb=\'[{"label":"Inicio","url":"/"},{"label":"Productos"}]\']');

        $this->assertStringContainsString('breadcrumb', $result);
        $this->assertStringContainsString('Inicio', $result);
        $this->assertStringContainsString('Productos', $result);
        $this->assertStringContainsString('delimiter', $result);
    }

    public function test_page_header_inline_layout_adds_correct_classes(): void
    {
        $result = shortcode('[page-header title="Tienda" layout="inline"]');

        $this->assertStringContainsString('bread-inline', $result);
        $this->assertStringContainsString('breadcrumb-resize-padding', $result);
    }

    public function test_page_header_subtitle_renders_when_provided(): void
    {
        $result = shortcode('[page-header title="Equipo" subtitle="Conoce a nuestro equipo"]');

        $this->assertStringContainsString('page-subtitle', $result);
        $this->assertStringContainsString('Conoce a nuestro equipo', $result);
    }

    public function test_page_header_min_height_sm_applies_class(): void
    {
        $result = shortcode('[page-header title="Blog" min-height="sm"]');

        $this->assertStringContainsString('page-header-sm', $result);
    }

    // =========================================================================
    // [social-links]
    // =========================================================================

    public function test_social_links_renders_facebook_and_instagram(): void
    {
        $result = shortcode('[social-links networks=\'{"facebook":"https://fb.com/marca","instagram":"https://ig.com/marca"}\']');

        $this->assertStringContainsString('social-links', $result);
        $this->assertStringContainsString('fab fa-facebook-f', $result);
        $this->assertStringContainsString('fab fa-instagram', $result);
        $this->assertStringContainsString('https://fb.com/marca', $result);
    }

    public function test_social_links_returns_empty_without_networks(): void
    {
        $result = shortcode('[social-links style="rounded"]');

        $this->assertSame('', $result);
    }

    public function test_social_links_rounded_style_applies_class(): void
    {
        $result = shortcode('[social-links style="rounded" networks=\'{"facebook":"https://fb.com"}\']');

        $this->assertStringContainsString('rounded-link', $result);
    }

    public function test_social_links_square_style_applies_class(): void
    {
        $result = shortcode('[social-links style="square" networks=\'{"twitter":"https://twitter.com"}\']');

        $this->assertStringContainsString('square-link', $result);
    }

    public function test_social_links_active_style_applies_class(): void
    {
        $result = shortcode('[social-links style="active" networks=\'{"linkedin":"https://linkedin.com"}\']');

        $this->assertStringContainsString('social-link-active', $result);
    }

    public function test_social_links_renders_all_13_networks(): void
    {
        $networks = json_encode([
            'facebook' => 'https://fb.com',
            'twitter' => 'https://twitter.com',
            'x' => 'https://x.com',
            'instagram' => 'https://ig.com',
            'linkedin' => 'https://linkedin.com',
            'youtube' => 'https://youtube.com',
            'pinterest' => 'https://pinterest.com',
            'tiktok' => 'https://tiktok.com',
            'whatsapp' => 'https://wa.me/34600000000',
            'telegram' => 'https://t.me/marca',
            'github' => 'https://github.com',
            'discord' => 'https://discord.gg',
            'email' => 'mailto:info@ejemplo.com',
        ]);

        $result = shortcode("[social-links networks='{$networks}']");

        $this->assertStringContainsString('fab fa-facebook-f', $result);
        $this->assertStringContainsString('fab fa-x-twitter', $result);
        $this->assertStringContainsString('fab fa-tiktok', $result);
        $this->assertStringContainsString('fab fa-telegram', $result);
        $this->assertStringContainsString('fab fa-discord', $result);
        $this->assertStringContainsString('fas fa-envelope', $result);
    }

    public function test_social_links_unsupported_network_is_ignored(): void
    {
        $result = shortcode('[social-links networks=\'{"snapchat":"https://snapchat.com","facebook":"https://fb.com"}\']');

        $this->assertStringNotContainsString('snapchat', $result);
        $this->assertStringContainsString('fab fa-facebook-f', $result);
    }

    public function test_social_links_blank_target_adds_rel_noopener(): void
    {
        $result = shortcode('[social-links networks=\'{"facebook":"https://fb.com"}\' target="_blank"]');

        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    public function test_social_links_self_target_has_no_rel(): void
    {
        $result = shortcode('[social-links networks=\'{"facebook":"https://fb.com"}\' target="_self"]');

        $this->assertStringNotContainsString('noopener', $result);
    }

    public function test_social_links_center_align_on_inline_style_adds_justify(): void
    {
        $result = shortcode('[social-links networks=\'{"facebook":"https://fb.com"}\' style="inline" align="center"]');

        $this->assertStringContainsString('justify-content-center', $result);
    }

    // =========================================================================
    // [image-box]
    // =========================================================================

    public function test_image_box_renders_default_layout_with_name_and_job(): void
    {
        $result = shortcode('[image-box image="/team/ana.jpg" name="Ana García" job="CEO"]');

        $this->assertStringContainsString('image-box', $result);
        $this->assertStringContainsString('/team/ana.jpg', $result);
        $this->assertStringContainsString('image-box-name', $result);
        $this->assertStringContainsString('Ana García', $result);
        $this->assertStringContainsString('image-box-job', $result);
        $this->assertStringContainsString('CEO', $result);
    }

    public function test_image_box_returns_empty_without_image(): void
    {
        $result = shortcode('[image-box name="Ana García" job="CEO"]');

        $this->assertSame('', $result);
    }

    public function test_image_box_classic_layout_has_overlay(): void
    {
        $result = shortcode('[image-box image="/team/luis.jpg" name="Luis" job="Diseñador" layout="classic" social-instagram="https://ig.com/luis"]');

        $this->assertStringContainsString('overlay social-links', $result);
    }

    public function test_image_box_overlay_layout_has_overlay_visible(): void
    {
        $result = shortcode('[image-box image="/team/maria.jpg" name="María" layout="overlay" email="maria@empresa.com"]');

        $this->assertStringContainsString('image-overlay', $result);
        $this->assertStringContainsString('overlay-visible', $result);
        $this->assertStringContainsString('maria@empresa.com', $result);
    }

    public function test_image_box_overlay_without_contact_degrades_to_default(): void
    {
        $result = shortcode('[image-box image="/team/juan.jpg" name="Juan" layout="overlay"]');

        $this->assertStringNotContainsString('image-overlay', $result);
        $this->assertStringContainsString('image-box-name', $result);
    }

    public function test_image_box_renders_social_links(): void
    {
        $result = shortcode('[image-box image="/team/ana.jpg" name="Ana" social-facebook="https://fb.com/ana" social-linkedin="https://linkedin.com/in/ana"]');

        $this->assertStringContainsString('fab fa-facebook-f', $result);
        $this->assertStringContainsString('fab fa-linkedin-in', $result);
        $this->assertStringContainsString('https://fb.com/ana', $result);
    }

    public function test_image_box_without_social_links_has_no_social_div(): void
    {
        $result = shortcode('[image-box image="/team/ana.jpg" name="Ana" job="CEO"]');

        $this->assertStringNotContainsString('social-links', $result);
    }

    public function test_image_box_radius_false_has_no_banner_radius(): void
    {
        $result = shortcode('[image-box image="/team/ana.jpg" name="Ana" radius="false"]');

        $this->assertStringNotContainsString('banner-radius', $result);
    }

    public function test_image_box_radius_true_adds_banner_radius(): void
    {
        $result = shortcode('[image-box image="/team/ana.jpg" name="Ana" radius="true"]');

        $this->assertStringContainsString('banner-radius', $result);
    }

    public function test_image_box_animation_adds_appear_animate_class(): void
    {
        $result = shortcode('[image-box image="/team/ana.jpg" name="Ana" animation="fadeInLeft" animation-delay=".2s"]');

        $this->assertStringContainsString('appear-animate', $result);
        $this->assertStringContainsString('fadeInLeft', $result);
        $this->assertStringContainsString('.2s', $result);
    }

    public function test_image_box_phone_renders_tel_link_in_overlay(): void
    {
        $result = shortcode('[image-box image="/store/madrid.jpg" name="Madrid" layout="overlay" phone="+34 91 123 4567" email="madrid@empresa.com"]');

        $this->assertStringContainsString('tel:', $result);
        $this->assertStringContainsString('+34', $result);
    }

    // =========================================================================
    // [video]
    // =========================================================================

    public function test_video_returns_empty_without_src(): void
    {
        $result = shortcode('[video mode="popup" cover="/img/cover.jpg"]');

        $this->assertSame('', $result);
    }

    public function test_video_popup_mode_renders_banner_and_modal(): void
    {
        $result = shortcode('[video src="/video/intro.mp4" mode="popup" cover="/img/cover.jpg" title="Intro" subtitle="Subtítulo"]');

        $this->assertStringContainsString('popup-video', $result);
        $this->assertStringContainsString('banner-title', $result);
        $this->assertStringContainsString('Intro', $result);
        $this->assertStringContainsString('Subtítulo', $result);
        $this->assertStringContainsString('btn-play', $result);
        $this->assertStringContainsString('modal', $result);
        $this->assertStringContainsString('fas fa-play', $result);
    }

    public function test_video_popup_with_cover_sets_data_bg_image(): void
    {
        $result = shortcode('[video src="/video/intro.mp4" mode="popup" cover="/img/cover.jpg"]');

        $this->assertStringContainsString('data-bg-image="/img/cover.jpg"', $result);
    }

    public function test_video_inline_mp4_renders_video_element(): void
    {
        $result = shortcode('[video src="/video/demo.mp4" mode="inline"]');

        $this->assertStringContainsString('video-inline', $result);
        $this->assertStringContainsString('<video', $result);
        $this->assertStringContainsString('<source', $result);
        $this->assertStringContainsString('/video/demo.mp4', $result);
    }

    public function test_video_inline_youtube_renders_iframe(): void
    {
        $result = shortcode('[video src="https://www.youtube.com/watch?v=dQw4w9WgXcQ" mode="inline"]');

        $this->assertStringContainsString('video-iframe-wrapper', $result);
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('youtube.com', $result);
    }

    public function test_video_background_mode_renders_autoplay_muted_loop(): void
    {
        $result = shortcode('[video src="/video/loop.mp4" mode="background" autoplay="true" loop="true"]');

        $this->assertStringContainsString('video-background', $result);
        $this->assertStringContainsString('autoplay', $result);
        $this->assertStringContainsString('loop', $result);
        $this->assertStringContainsString('muted', $result);
        $this->assertStringContainsString('playsinline', $result);
    }

    public function test_video_youtube_background_degrades_to_popup(): void
    {
        $result = shortcode('[video src="https://www.youtube.com/watch?v=test" mode="background" cover="/img/cover.jpg"]');

        $this->assertStringContainsString('popup-video', $result);
        $this->assertStringNotContainsString('video-background', $result);
    }

    public function test_video_autoplay_forces_muted(): void
    {
        $result = shortcode('[video src="/video/demo.mp4" mode="inline" autoplay="true"]');

        $this->assertStringContainsString('muted', $result);
    }

    public function test_video_inline_with_cover_sets_poster(): void
    {
        $result = shortcode('[video src="/video/demo.mp4" mode="inline" cover="/img/poster.jpg"]');

        $this->assertStringContainsString('poster="/img/poster.jpg"', $result);
    }

    public function test_video_popup_youtube_renders_iframe_in_modal(): void
    {
        $result = shortcode('[video src="https://youtu.be/dQw4w9WgXcQ" mode="popup" cover="/img/cover.jpg"]');

        $this->assertStringContainsString('popup-video', $result);
        $this->assertStringContainsString('<iframe', $result);
        $this->assertStringContainsString('youtu.be', $result);
    }
}
