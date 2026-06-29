<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Template\Theme\Theme;

/**
 * Configuración de la plantilla Wowy para inoqualabs.
 */

/**
 * Detecta el template de la página actual basándose en la URL.
 * Cachea el resultado por 1 hora para evitar queries repetidas.
 */
if (! function_exists('wowy_get_current_template')) {
    function wowy_get_current_template(): string
    {
        $path = trim(request()->path(), '/');
        $cacheKey = 'theme.template:'.$path;

        return Cache::remember($cacheKey, now()->addHour(), function () use ($path) {
            $slug = $path === '' ? 'home' : $path;

            $page = DB::table('pages')
                ->join('page_translations', 'pages.id', '=', 'page_translations.page_id')
                ->where('page_translations.slug', $slug)
                ->where('page_translations.status', 'published')
                ->select('pages.template')
                ->first();

            return $page->template ?? 'default';
        });
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | Inherit from another template
    |--------------------------------------------------------------------------
    */
    'inherit' => null,

    /*
    |--------------------------------------------------------------------------
    | Theme Events — registers CSS/JS assets via the Theme engine
    |--------------------------------------------------------------------------
    */
    'events' => [
        'beforeRenderTheme' => function (Theme $theme): void {
            $template = wowy_get_current_template();
            $isHome = $template === 'homepage';

            // ===== CSS BASE (todas las páginas) =====
            $theme->asset()->usePath()->add('normalize-css', 'css/vendors/normalize.css');
            $theme->asset()->usePath()->add('bootstrap-css', 'plugins/bootstrap/css/bootstrap.min.css');
            $theme->asset()->usePath()->add('fontawesome', 'plugins/fontawesome/css/all.css');
            $theme->asset()->usePath()->add('wowy-font-css', 'css/vendors/wowy-font.css');
            $theme->asset()->usePath()->add('animate-css', 'css/plugins/animate.css');
            $theme->asset()->usePath()->add('slick-css', 'css/plugins/slick.css');
            $theme->asset()->usePath()->add('style-css', 'css/style.css');

            // ===== JS BASE (todas las páginas) =====
            $theme->asset()->container('footer')->usePath()->add('modernizr', 'js/vendor/modernizr-3.6.0.min.js');
            $theme->asset()->container('footer')->usePath()->add('jquery', 'js/vendor/jquery.min.js');
            $theme->asset()->container('footer')->usePath()->add('jquery-migrate', 'js/vendor/jquery-migrate.min.js');
            $theme->asset()->container('footer')->usePath()->add('bootstrap-js', 'plugins/bootstrap/js/bootstrap.bundle.min.js');
            $theme->asset()->container('footer')->usePath()->add('slick-js', 'js/plugins/slick.js');
            $theme->asset()->container('footer')->usePath()->add('syotimer-js', 'js/plugins/jquery.syotimer.min.js');
            $theme->asset()->container('footer')->usePath()->add('wow-js', 'js/plugins/wow.js');
            $theme->asset()->container('footer')->usePath()->add('waypoints-js', 'js/plugins/waypoints.js');
            $theme->asset()->container('footer')->usePath()->add('countdown-js', 'js/plugins/jquery.countdown.min.js');
            $theme->asset()->container('footer')->usePath()->add('vticker-js', 'js/plugins/jquery.vticker-min.js');
            $theme->asset()->container('footer')->usePath()->add('main', 'js/main.js');
            $theme->asset()->container('footer')->usePath()->add('backend', 'js/backend.js');
        },
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Options
    |--------------------------------------------------------------------------
    */
    'options' => [
        'sidebar_position' => 'right',
        'layout_type' => 'ecommerce',
        'max_width' => '100%',
        'rtl_support' => true,
        'responsive' => true,
        'ecommerce_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Layouts
    |--------------------------------------------------------------------------
    */
    'layouts' => [
        'default' => 'Diseño por defecto',
        'homepage' => 'Página de inicio',
        'full-width' => 'Ancho completo',
        'blog-left-sidebar' => 'Blog con sidebar izquierdo',
        'blog-right-sidebar' => 'Blog con sidebar derecho',
        'blog-full-width' => 'Blog a ancho completo',
        'product-left-sidebar' => 'Producto con sidebar izquierdo',
        'product-right-sidebar' => 'Producto con sidebar derecho',
        'product-full-width' => 'Producto a ancho completo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependencies / Required Modules
    |--------------------------------------------------------------------------
    */
    'required_modules' => [
        'ecommerce' => 'Ecommerce module (for product pages, cart, checkout)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        'products-carousel' => 'Carrusel de productos',
        'categories-menu' => 'Menú de categorías',
        'featured-products' => 'Productos destacados',
        'sale-products' => 'Productos en oferta',
        'newsletter-signup' => 'Suscripción a newsletter',
        'testimonials' => 'Testimonios de clientes',
        'brand-logos' => 'Logos de marcas',
        'special-offer' => 'Ofertas especiales',
    ],
];
