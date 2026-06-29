<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Template\Theme\Theme;

/**
 * Configuración de la plantilla Caixilharia Blanco para inoqualabs.
 */

/**
 * Detecta el template de la página actual basándose en la URL.
 * Cachea el resultado por 1 hora para evitar queries repetidas.
 */
if (! function_exists('caixilhariablanco_get_current_template')) {
    function caixilhariablanco_get_current_template(): string
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
            $template = caixilhariablanco_get_current_template();
            $isHome = $template === 'homepage';

            // ===== CSS BASE (todas las páginas) =====
            // ===== CSS BASE (todas las páginas) =====
            $theme->asset()->usePath()->add('bootstrap-css', 'css/bootstrap.min.css', [], [], '3');
            $theme->asset()->usePath()->add('fontawesome-css', 'css/all.min.css', [], [], '3');
            $theme->asset()->usePath()->add('animate-css', 'css/animate.css', [], [], '3');
            $theme->asset()->usePath()->add('mousecursor-css', 'css/mousecursor.css', [], [], '3');
            $theme->asset()->usePath()->add('styles-css', 'css/styles.min.css', [], [], '6');

            // ===== CSS POR GRUPO =====
            // Select2 (todos los select del sitio)
            $theme->asset()->add('select2-css', asset('core/select2/css/select2.min.css'), [], [], '3');

            if ($isHome) {
                // Homepage: owlcarousel + aos
                $theme->asset()->usePath()->add('owlcarousel-css', 'css/plugins/owlcarousel.min.css', [], [], '3');
                $theme->asset()->usePath()->add('aos-css', 'css/plugins/aos.css', [], [], '3');
            } else {
                // Páginas internas: magnific-popup (galerías)
                $theme->asset()->usePath()->add('magnific-css', 'css/magnific-popup.css', [], [], '3');

                // aos solo en estimate (formularios con animaciones)
                if ($template === 'estimate') {
                    $theme->asset()->usePath()->add('aos-css', 'css/plugins/aos.css', [], [], '3');
                }
            }

            // ===== JS BASE (todas las páginas) =====
            $theme->asset()->container('footer')->usePath()->add('jquery', 'js/jquery-3.7.1.min.js', [], [], '3');
            $theme->asset()->container('footer')->usePath()->add('bootstrap-js', 'js/bootstrap.min.js', [], [], '3');
            $theme->asset()->container('footer')->usePath()->add('wow-js', 'js/wow.min.js', [], [], '3');

            // ===== JS POR GRUPO =====
            // Select2 (todos los select del sitio)
            $theme->asset()->container('footer')->add('select2-js', asset('core/select2/js/select2.min.js'), [], [], '3');

            if ($isHome) {
                // Homepage: hero slider, counter, aos
                $theme->asset()->container('footer')->usePath()->add('waypoints-js', 'js/jquery.waypoints.min.js', [], [], '3');
                $theme->asset()->container('footer')->usePath()->add('counterup-js', 'js/jquery.counterup.min.js', [], [], '3');
                $theme->asset()->container('footer')->usePath()->add('aos-js', 'js/plugins/aos.js', [], [], '3');
                $theme->asset()->container('footer')->usePath()->add('owlcarousel-js', 'js/plugins/owlcarousel.min.js', [], [], '3');
                $theme->asset()->container('footer')->usePath()->add('main-js', 'js/main-home.min.js', [], [], '6');
            } else {
                // Páginas internas: magnific-popup (galerías)
                $theme->asset()->container('footer')->usePath()->add('magnific-js', 'js/jquery.magnific-popup.min.js', [], [], '3');

                // aos solo en estimate
                if ($template === 'estimate') {
                    $theme->asset()->container('footer')->usePath()->add('aos-js', 'js/plugins/aos.js', [], [], '3');
                }

                $theme->asset()->container('footer')->usePath()->add('main-js', 'js/main-inner.min.js', [], [], '3');
            }
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
    | Esta plantilla proporciona múltiples layouts para diferentes tipos de contenido
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
    | Template Assets
    |--------------------------------------------------------------------------
    | Assets principales de la plantilla Wowy
    */
    'assets' => [
        /*
         * Rutas relativas al directorio del tema (platform/themes/caixilhariablanco/).
         * Estos archivos son servidos vía el symlink public/pages → public/.
         * Los vendors ya están en public/; style.css y rtl.css se generan con webpack.
         */
        'css' => [
            // Vendors (ya presentes en public/css/)
            'bootstrap' => 'public/css/bootstrap.min.css',
            'fontawesome' => 'public/css/all.min.css',
            'animate' => 'public/css/animate.css',
            'magnific-popup' => 'public/css/magnific-popup.css',
            'mousecursor' => 'public/css/mousecursor.css',
            'owlcarousel' => 'public/css/plugins/owlcarousel.min.css',

            // CSS unificado
            'styles' => 'public/css/styles.min.css',
            'rtl' => 'public/css/rtl.css',
        ],
        'js' => [
            // Vendors (ya presentes en public/js/)
            'jquery' => 'public/js/jquery-3.7.1.min.js',
            'bootstrap' => 'public/js/bootstrap.min.js',
            'magnific-popup' => 'public/js/jquery.magnific-popup.min.js',
            'waypoints' => 'public/js/jquery.waypoints.min.js',
            'wow' => 'public/js/wow.min.js',
            'owlcarousel' => 'public/js/plugins/owlcarousel.min.js',
        ],
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
    | Widgets disponibles en esta plantilla
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
