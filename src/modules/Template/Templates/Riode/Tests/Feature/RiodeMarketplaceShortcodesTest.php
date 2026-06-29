<?php

namespace Modules\Template\Templates\Riode\Tests\Feature;

use Modules\Template\Templates\Riode\Tests\RiodeTestCase;

class RiodeMarketplaceShortcodesTest extends RiodeTestCase
{
    // =========================================================================
    // [instagram-feed]
    // =========================================================================

    /** @test */
    public function test_instagram_feed_renders_grid_with_manual_images(): void
    {
        $images = json_encode([
            ['url' => '/img/1.jpg', 'link' => 'https://instagram.com/p/abc', 'alt' => 'Foto 1'],
            ['url' => '/img/2.jpg', 'link' => 'https://instagram.com/p/def', 'alt' => 'Foto 2'],
        ]);

        $result = shortcode("[instagram-feed layout=\"grid\" cols=\"5\" images='{$images}']");

        $this->assertStringContainsString('instagram', $result);
        $this->assertStringContainsString('/img/1.jpg', $result);
        $this->assertStringContainsString('/img/2.jpg', $result);
        $this->assertStringContainsString('loading="lazy"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    /** @test */
    public function test_instagram_feed_carousel_includes_owl_options(): void
    {
        $images = json_encode([
            ['url' => '/img/1.jpg', 'link' => '#', 'alt' => 'A'],
            ['url' => '/img/2.jpg', 'link' => '#', 'alt' => 'B'],
        ]);

        $result = shortcode("[instagram-feed layout=\"carousel\" cols=\"5\" images='{$images}']");

        $this->assertStringContainsString('owl-carousel', $result);
        $this->assertStringContainsString('data-owl-options', $result);
    }

    /** @test */
    public function test_instagram_feed_masonry_renders_height_classes(): void
    {
        $images = json_encode([
            ['url' => '/img/big.jpg', 'link' => '#', 'alt' => 'Big', 'size' => 'x2'],
            ['url' => '/img/sm.jpg', 'link' => '#', 'alt' => 'Small', 'size' => 'x1'],
        ]);

        $result = shortcode("[instagram-feed layout=\"masonry\" images='{$images}']");

        $this->assertStringContainsString('instagram-masonry', $result);
        $this->assertStringContainsString('height-x2', $result);
        $this->assertStringContainsString('height-x1', $result);
        $this->assertStringContainsString('col-md-6', $result);
    }

    /** @test */
    public function test_instagram_feed_returns_empty_without_images_in_manual_mode(): void
    {
        $result = shortcode('[instagram-feed layout="grid" cols="4"]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_instagram_feed_respects_limit_attribute(): void
    {
        $images = json_encode(array_map(function ($i) {
            return ['url' => "/img/{$i}.jpg", 'link' => '#', 'alt' => "Foto {$i}"];
        }, range(1, 10)));

        $result = shortcode("[instagram-feed layout=\"grid\" limit=\"3\" images='{$images}']");

        $this->assertStringContainsString('/img/1.jpg', $result);
        $this->assertStringContainsString('/img/3.jpg', $result);
        $this->assertStringNotContainsString('/img/4.jpg', $result);
    }

    /** @test */
    public function test_instagram_feed_api_source_without_images_renders_placeholder(): void
    {
        $result = shortcode('[instagram-feed source="api" layout="grid"]');

        $this->assertStringContainsString('instagram-feed-placeholder', $result);
        $this->assertStringContainsString('fa-instagram', $result);
    }

    /** @test */
    public function test_instagram_feed_show_info_adds_instagram_info_class(): void
    {
        $images = json_encode([['url' => '/img/1.jpg', 'link' => '#', 'alt' => 'A']]);
        $result = shortcode("[instagram-feed layout=\"grid\" show-info=\"true\" images='{$images}']");

        $this->assertStringContainsString('instagram-info', $result);
    }

    // =========================================================================
    // [subcategory-card]
    // =========================================================================

    /** @test */
    public function test_subcategory_card_renders_with_required_attributes(): void
    {
        $result = shortcode('[subcategory-card name="Ventanas" href="/ventanas" icon="fas fa-window-maximize"]');

        $this->assertStringContainsString('category-group-icon', $result);
        $this->assertStringContainsString('Ventanas', $result);
        $this->assertStringContainsString('/ventanas', $result);
        $this->assertStringContainsString('fas fa-window-maximize', $result);
    }

    /** @test */
    public function test_subcategory_card_returns_empty_without_required_attributes(): void
    {
        $result = shortcode('[subcategory-card icon="fas fa-folder"]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_subcategory_card_renders_subcategories_list(): void
    {
        $subs = json_encode([
            ['label' => 'PVC', 'url' => '/pvc'],
            ['label' => 'Aluminio', 'url' => '/aluminio'],
        ]);

        $result = shortcode("[subcategory-card name=\"Ventanas\" href=\"/ventanas\" icon=\"fas fa-window-maximize\" subcategories='{$subs}']");

        $this->assertStringContainsString('category-list', $result);
        $this->assertStringContainsString('PVC', $result);
        $this->assertStringContainsString('Aluminio', $result);
        $this->assertStringContainsString('/pvc', $result);
    }

    /** @test */
    public function test_subcategory_card_omits_list_when_no_subcategories(): void
    {
        $result = shortcode('[subcategory-card name="Ventanas" href="/ventanas" icon="fas fa-window-maximize"]');

        $this->assertStringNotContainsString('category-list', $result);
    }

    /** @test */
    public function test_subcategory_card_sanitizes_invalid_bg_color(): void
    {
        $result = shortcode('[subcategory-card name="X" href="#" icon="fas fa-folder" bg-color="javascript:alert(1)"]');

        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('#4b5577', $result);
    }

    // =========================================================================
    // [category-column] + [category-box]
    // =========================================================================

    /** @test */
    public function test_category_column_renders_wrapper_with_child_boxes(): void
    {
        $links = json_encode([['label' => 'PVC', 'url' => '/pvc'], ['label' => 'Aluminio', 'url' => '/aluminio']]);

        $result = shortcode("[category-column cols=\"3\"][category-box title=\"Ventanas:\" links='{$links}'][/category-box][/category-column]");

        $this->assertStringContainsString('category-column', $result);
        $this->assertStringContainsString('category-box', $result);
        $this->assertStringContainsString('Ventanas:', $result);
        $this->assertStringContainsString('PVC', $result);
    }

    /** @test */
    public function test_category_column_returns_empty_with_no_content(): void
    {
        $result = shortcode('[category-column cols="4"][/category-column]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_category_column_gap_none_adds_gap_0_class(): void
    {
        $links = json_encode([['label' => 'A', 'url' => '/a']]);
        $result = shortcode("[category-column gap=\"none\"][category-box title=\"T\" links='{$links}'][/category-box][/category-column]");

        $this->assertStringContainsString('gap-0', $result);
    }

    /** @test */
    public function test_category_box_renders_links(): void
    {
        $links = json_encode([['label' => 'Correderas', 'url' => '/correderas'], ['label' => 'Basculantes', 'url' => '/basculantes']]);

        $result = shortcode("[category-box title=\"Puertas:\" links='{$links}']");

        $this->assertStringContainsString('category-box', $result);
        $this->assertStringContainsString('category-name', $result);
        $this->assertStringContainsString('Puertas:', $result);
        $this->assertStringContainsString('Correderas', $result);
        $this->assertStringContainsString('/correderas', $result);
    }

    /** @test */
    public function test_category_box_returns_empty_without_required_attrs(): void
    {
        $result = shortcode('[category-box title="Sin links"]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_category_box_limit_truncates_links(): void
    {
        $links = json_encode([
            ['label' => 'A', 'url' => '/a'],
            ['label' => 'B', 'url' => '/b'],
            ['label' => 'C', 'url' => '/c'],
        ]);

        $result = shortcode("[category-box title=\"Test\" limit=\"2\" links='{$links}']");

        $this->assertStringContainsString('A', $result);
        $this->assertStringContainsString('B', $result);
        $this->assertStringNotContainsString('>C<', $result);
    }

    // =========================================================================
    // [vendor-card]
    // =========================================================================

    /** @test */
    public function test_vendor_card_renders_with_required_attributes(): void
    {
        $result = shortcode('[vendor-card name="Carpintería López" href="/marketplace/lopez" logo="/img/logo.png"]');

        $this->assertStringContainsString('vendor-widget', $result);
        $this->assertStringContainsString('Carpintería López', $result);
        $this->assertStringContainsString('/marketplace/lopez', $result);
        $this->assertStringContainsString('/img/logo.png', $result);
        $this->assertStringContainsString('loading="lazy"', $result);
    }

    /** @test */
    public function test_vendor_card_returns_empty_without_required_attributes(): void
    {
        $result = shortcode('[vendor-card logo="/img/logo.png"]');

        $this->assertSame('', $result);
    }

    /** @test */
    public function test_vendor_card_style2_adds_vendor_type2_class(): void
    {
        $result = shortcode('[vendor-card name="Tienda" href="/tienda" logo="/logo.png" layout="style2"]');

        $this->assertStringContainsString('vendor-type2', $result);
    }

    /** @test */
    public function test_vendor_card_renders_products_count_when_provided(): void
    {
        $result = shortcode('[vendor-card name="Tienda" href="/tienda" logo="/logo.png" products-count="42"]');

        $this->assertStringContainsString('42', $result);
        $this->assertStringContainsString('Productos', $result);
    }

    /** @test */
    public function test_vendor_card_products_count_zero_shows_sin_productos(): void
    {
        $result = shortcode('[vendor-card name="Tienda" href="/tienda" logo="/logo.png" products-count="0"]');

        $this->assertStringContainsString('Sin productos', $result);
    }

    /** @test */
    public function test_vendor_card_hides_rating_when_not_provided(): void
    {
        $result = shortcode('[vendor-card name="Tienda" href="/tienda" logo="/logo.png"]');

        $this->assertStringNotContainsString('ratings-container', $result);
    }

    /** @test */
    public function test_vendor_card_renders_rating_when_provided(): void
    {
        $result = shortcode('[vendor-card name="Tienda" href="/tienda" logo="/logo.png" rating="80"]');

        $this->assertStringContainsString('ratings-container', $result);
        $this->assertStringContainsString('data-rating="80"', $result);
    }

    /** @test */
    public function test_vendor_card_renders_product_images(): void
    {
        $productImages = json_encode([
            ['url' => '/img/p1.jpg', 'href' => '/producto/1', 'alt' => 'Ventana'],
            ['url' => '/img/p2.jpg', 'href' => '/producto/2', 'alt' => 'Puerta'],
        ]);

        $result = shortcode("[vendor-card name=\"Tienda\" href=\"/tienda\" logo=\"/logo.png\" product-images='{$productImages}']");

        $this->assertStringContainsString('vendor-products', $result);
        $this->assertStringContainsString('/img/p1.jpg', $result);
        $this->assertStringContainsString('/img/p2.jpg', $result);
        $this->assertStringContainsString('Ventana', $result);
    }

    /** @test */
    public function test_vendor_card_rating_is_clamped_to_valid_range(): void
    {
        $result = shortcode('[vendor-card name="T" href="#" logo="/l.png" rating="150"]');

        $this->assertStringContainsString('data-rating="100"', $result);
    }
}
