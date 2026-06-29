<?php

/**
 * Funciones de compatibilidad - Plantilla Wowy para inoqualabs
 *
 * Esta plantilla fue originalmente diseñada para Botble CMS.
 * Estas funciones proveen stubs y adaptaciones para que Wowy
 * funcione con el sistema de Templates de inoqualabs.
 */

use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Ecommerce\Services\CartService;

// ---------------------------------------------------------------------------
// Registro de templates de página
// ---------------------------------------------------------------------------
if (! function_exists('register_page_template')) {
    function register_page_template($templates)
    {
        return true;
    }
}

if (! function_exists('register_sidebar')) {
    function register_sidebar($sidebar)
    {
        return true;
    }
}

// ---------------------------------------------------------------------------
// Moneda y precios
// ---------------------------------------------------------------------------
if (! function_exists('get_application_currency')) {
    function get_application_currency()
    {
        return (object) [
            'symbol' => '$',
            'title' => 'USD',
            'decimals' => 2,
            'is_prefix_symbol' => true,
        ];
    }
}

if (! function_exists('get_application_currency_id')) {
    function get_application_currency_id()
    {
        return 1;
    }
}

if (! function_exists('get_all_currencies')) {
    function get_all_currencies()
    {
        return [get_application_currency()];
    }
}

if (! function_exists('get_currencies_json')) {
    function get_currencies_json(): array
    {
        $currency = get_application_currency();

        return [
            'display_big_money' => false,
            'billion' => __('billion'),
            'million' => __('million'),
            'is_prefix_symbol' => $currency->is_prefix_symbol,
            'symbol' => $currency->symbol,
            'title' => $currency->title,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'number_after_dot' => $currency->decimals ?? 2,
            'show_symbol_or_title' => true,
        ];
    }
}

if (! function_exists('format_price')) {
    function format_price(float $price, ?object $currency = null): string
    {
        $currency = $currency ?? get_application_currency();
        $formatted = number_format($price, $currency->decimals ?? 2);

        return $currency->is_prefix_symbol
            ? ($currency->symbol ?? '$').$formatted
            : $formatted.($currency->symbol ?? '$');
    }
}

if (! function_exists('get_ecommerce_setting')) {
    function get_ecommerce_setting($key, $default = null)
    {
        return $default;
    }
}

// ---------------------------------------------------------------------------
// Layouts y estilos
// ---------------------------------------------------------------------------
if (! function_exists('get_blog_single_layouts')) {
    function get_blog_single_layouts(): array
    {
        return [
            '' => __('Inherit'),
            'blog-right-sidebar' => __('Blog Right Sidebar'),
            'blog-left-sidebar' => __('Blog Left Sidebar'),
            'blog-full-width' => __('Full width'),
        ];
    }
}

if (! function_exists('get_product_single_layouts')) {
    function get_product_single_layouts(): array
    {
        return [
            '' => __('Inherit'),
            'product-right-sidebar' => __('Product Right Sidebar'),
            'product-left-sidebar' => __('Product Left Sidebar'),
            'product-full-width' => __('Product Full Width'),
        ];
    }
}

if (! function_exists('get_layout_header_styles')) {
    function get_layout_header_styles(): array
    {
        return [
            'header-style-1' => __('Default'),
            'header-style-2' => __('Header style 2'),
            'header-style-3' => __('Header style 3'),
            'header-style-4' => __('Header style 4'),
        ];
    }
}

if (! function_exists('get_simple_slider_styles')) {
    function get_simple_slider_styles(): array
    {
        return [
            'style-1' => __('Default - Full width'),
            'style-2' => __('Full width - text center'),
            'style-3' => __('With Ads'),
            'style-4' => __('Limit width'),
        ];
    }
}

if (! function_exists('theme_get_autoplay_speed_options')) {
    function theme_get_autoplay_speed_options(): array
    {
        return array_combine([2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000], [2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000]);
    }
}

// ---------------------------------------------------------------------------
// Helpers de tema
// ---------------------------------------------------------------------------
if (! function_exists('theme_url')) {
    function theme_url(string $path = ''): string
    {
        $theme = setting('template', 'wowy');

        return url('themes/'.$theme.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $theme = setting('template', 'wowy');

        return asset('themes/'.$theme.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('theme_image')) {
    function theme_image(string $path): string
    {
        return theme_asset('images/'.ltrim($path, '/'));
    }
}

// ---------------------------------------------------------------------------
// Compatibilidad con EcommerceHelper de Botble
// ---------------------------------------------------------------------------
if (! function_exists('EcommerceHelper')) {
    class EcommerceHelperStub
    {
        public static function isCartEnabled(): bool
        {
            return true;
        }

        public static function isWishlistEnabled(): bool
        {
            return true;
        }

        public static function isReviewEnabled(): bool
        {
            return false;
        }

        public static function isQuickBuyButtonEnabled(): bool
        {
            return false;
        }

        public static function isOrderTrackingEnabled(): bool
        {
            return false;
        }

        public static function isTaxEnabled(): bool
        {
            return false;
        }

        public static function isProductSpecificationEnabled(): bool
        {
            return false;
        }

        public static function viewPath(string $view): string
        {
            return 'ecommerce::'.$view;
        }

        public static function withReviewsParams(): array
        {
            return [];
        }

        public static function withProductEagerLoadingRelations(): array
        {
            return ['brand', 'categories'];
        }

        public static function dataForFilter(?object $category = null): array
        {
            return [
                collect(),
                collect(),
                collect(),
                rand(),
                [],
                url()->current(),
                $category?->id ?? 0,
                0,
            ];
        }

        public static function getSortParams(): array
        {
            return [
                'default_sorting' => __('Default'),
                'date_asc' => __('Oldest'),
                'date_desc' => __('Newest'),
                'price_asc' => __('Price: Low to High'),
                'price_desc' => __('Price: High to Low'),
                'name_asc' => __('Name: A-Z'),
                'name_desc' => __('Name: Z-A'),
            ];
        }

        public static function getShowParams(): array
        {
            return [12 => 12, 24 => 24, 36 => 36];
        }
    }
}

if (! class_exists('EcommerceHelper')) {
    class_alias('EcommerceHelperStub', 'EcommerceHelper');
}

if (! function_exists('get_sale_percentage')) {
    function get_sale_percentage(?float $price, ?float $salePrice): ?string
    {
        if (! $price || ! $salePrice || $price <= 0 || $salePrice >= $price) {
            return null;
        }

        return round((1 - $salePrice / $price) * 100).'%';
    }
}

if (! function_exists('get_cross_sale_products')) {
    function get_cross_sale_products($product, int $limit = 4)
    {
        return [];
    }
}

if (! function_exists('get_related_products')) {
    function get_related_products($product, int $limit = 4)
    {
        if (! $product || ! $product->categories) {
            return [];
        }

        return Product::query()
            ->where('status', 'published')
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($q) use ($product) {
                $q->whereIn('ecommerce_product_categories.id', $product->categories->pluck('id'));
            })
            ->limit($limit)
            ->get();
    }
}

// ---------------------------------------------------------------------------
// Compatibilidad con MetaBox de Botble
// ---------------------------------------------------------------------------
if (! class_exists('MetaBox')) {
    class MetaBox
    {
        public static function getMetaData($model, string $key, bool $single = false)
        {
            return null;
        }

        public static function saveMetaBoxData($model, string $key, $value): void
        {
            // No-op
        }
    }
}

// ---------------------------------------------------------------------------
// Compatibilidad con Cart de Botble (usa nuestro CartService)
// ---------------------------------------------------------------------------
if (! function_exists('get_time_to_read')) {
    function get_time_to_read($post, int $wordsPerMinute = 200): int
    {
        $content = strip_tags($post->content ?? '');
        $wordCount = str_word_count($content);

        return max(1, (int) ceil($wordCount / $wordsPerMinute));
    }
}

if (! class_exists('BotbleCartStub')) {
    class BotbleCartStub
    {
        protected static ?CartService $service = null;

        protected static function service(): CartService
        {
            return self::$service ??= app(CartService::class);
        }

        public static function instance(string $name = 'cart'): self
        {
            return new self;
        }

        public function count(): int
        {
            return self::service()->count();
        }

        public function content()
        {
            $items = self::service()->getCartItems();

            return $items->map(function ($item) {
                return (object) [
                    'rowId' => $item->id,
                    'id' => $item->product_id,
                    'qty' => $item->qty,
                    'price' => $item->product?->final_price ?? 0,
                    'options' => (object) [
                        'image' => $item->product?->featured_image ?? '',
                        'attributes' => '',
                    ],
                ];
            });
        }

        public function isNotEmpty(): bool
        {
            return self::service()->count() > 0;
        }

        public function rawSubTotal(): float
        {
            return self::service()->getCartItems()->sum(
                fn ($item) => ($item->product?->final_price ?? 0) * $item->qty
            );
        }

        public function rawTax(): float
        {
            return 0;
        }

        public function rawTotal(): float
        {
            return $this->rawSubTotal();
        }
    }
}

if (! class_exists('Cart')) {
    class_alias('BotbleCartStub', 'Cart');
}

if (! class_exists('DashboardMenu')) {
    class DashboardMenu
    {
        public static function getAll(string $type): array
        {
            return [];
        }
    }
}

// ---------------------------------------------------------------------------
// Constantes de compatibilidad con Botble
// ---------------------------------------------------------------------------
if (! defined('PAGE_FILTER_FRONT_PAGE_CONTENT')) {
    define('PAGE_FILTER_FRONT_PAGE_CONTENT', 'page-filter-front-page-content');
}

if (! defined('BASE_FILTER_PUBLIC_COMMENT_AREA')) {
    define('BASE_FILTER_PUBLIC_COMMENT_AREA', 'base-filter-public-comment-area');
}

if (! defined('BASE_FILTER_BEFORE_RENDER_FORM')) {
    define('BASE_FILTER_BEFORE_RENDER_FORM', 'base-filter-before-render-form');
}

if (! defined('BASE_ACTION_META_BOXES')) {
    define('BASE_ACTION_META_BOXES', 'base-action-meta-boxes');
}

if (! defined('BASE_ACTION_AFTER_CREATE_CONTENT')) {
    define('BASE_ACTION_AFTER_CREATE_CONTENT', 'base-action-after-create-content');
}

if (! defined('BASE_ACTION_AFTER_UPDATE_CONTENT')) {
    define('BASE_ACTION_AFTER_UPDATE_CONTENT', 'base-action-after-update-content');
}

if (! defined('ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML')) {
    define('ECOMMERCE_PRODUCT_DETAIL_EXTRA_HTML', 'ecommerce-product-detail-extra-html');
}

if (! function_exists('add_shortcode')) {
    /**
     * Registra un shortcode en el compilador del módulo Shortcode.
     * Compatible con la firma de Botble: add_shortcode(key, name, description, callback).
     */
    function add_shortcode(string $key, string $name, string $description, callable $callback): void
    {
        if (! app()->bound('shortcode')) {
            return;
        }

        app('shortcode')->register($key, function (array $attrs, string $content) use ($callback): string {
            try {
                return (string) call_user_func($callback, (object) $attrs, $content);
            } catch (Throwable $e) {
                return '';
            }
        });
    }
}

if (! function_exists('shortcode_manager')) {
    function shortcode_manager(): mixed
    {
        return app('shortcode');
    }
}

if (! class_exists('Shortcode')) {
    class Shortcode extends stdClass {}
}

// ---------------------------------------------------------------------------
// Helpers de productos (compatibilidad con funciones de Botble)
// ---------------------------------------------------------------------------
if (! function_exists('get_featured_product_categories')) {
    function get_featured_product_categories(int $limit = 10)
    {
        if (! class_exists(ProductCategory::class)) {
            return collect();
        }

        return ProductCategory::query()
            ->where('status', 'published')
            ->where('is_featured', true)
            ->limit($limit)
            ->get();
    }
}

if (! function_exists('get_featured_products')) {
    function get_featured_products(array $params = [])
    {
        if (! class_exists(Product::class)) {
            return collect();
        }

        $query = Product::query()
            ->where('status', 'published')
            ->where('is_featured', true);

        if (! empty($params['take'])) {
            $query->limit((int) $params['take']);
        }

        if (! empty($params['with'])) {
            $query->with((array) $params['with']);
        }

        return $query->get();
    }
}

if (! function_exists('get_featured_brands')) {
    function get_featured_brands(int $limit = 10)
    {
        if (! class_exists(Brand::class)) {
            return collect();
        }

        return Brand::query()
            ->where('status', 'published')
            ->where('is_featured', true)
            ->limit($limit)
            ->get();
    }
}

if (! function_exists('get_product_collections')) {
    function get_product_collections(array $condition = [], array $orderBy = [], array $select = [])
    {
        if (! class_exists(ProductCollection::class)) {
            return collect();
        }

        $query = ProductCollection::query()
            ->where('status', 'published');

        if (! empty($select)) {
            $query->select($select);
        }

        return $query->get();
    }
}

if (! function_exists('get_products_by_collections')) {
    function get_products_by_collections(array $params = [])
    {
        if (! class_exists(Product::class)) {
            return collect();
        }

        $query = Product::query()
            ->where('status', 'published');

        if (! empty($params['collections']['value_in'])) {
            $query->whereHas('collections', function ($q) use ($params) {
                $q->whereIn('ecommerce_product_collections.id', (array) $params['collections']['value_in']);
            });
        }

        if (! empty($params['take'])) {
            $query->limit((int) $params['take']);
        }

        if (! empty($params['with'])) {
            $query->with((array) $params['with']);
        }

        return $query->get();
    }
}

if (! function_exists('get_products_by_categories')) {
    function get_products_by_categories(array $params = [])
    {
        if (! class_exists(Product::class)) {
            return collect();
        }

        $query = Product::query()
            ->where('status', 'published');

        if (! empty($params['categories']['value_in'])) {
            $query->whereHas('categories', function ($q) use ($params) {
                $q->whereIn('ecommerce_product_categories.id', (array) $params['categories']['value_in']);
            });
        }

        if (! empty($params['take'])) {
            $query->limit((int) $params['take']);
        }

        return $query->get();
    }
}

// ---------------------------------------------------------------------------
// Stubs de Ads (seguridad si alguna vista las llama)
// ---------------------------------------------------------------------------
if (! function_exists('display_ad')) {
    function display_ad($ads, $class = ''): ?string
    {
        return null;
    }
}

if (! function_exists('get_ads_keys_from_shortcode')) {
    function get_ads_keys_from_shortcode(object $shortcode): array
    {
        return [];
    }
}

if (! function_exists('display_ads')) {
    function display_ads(array $keys): string
    {
        return '';
    }
}
