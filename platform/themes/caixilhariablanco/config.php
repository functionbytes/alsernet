<?php

use Modules\Template\Theme\Theme;

/**
 * Configuración de la plantilla Caixilharia Blanco para inoqualabs.
 */

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
            // CSS
            $theme->asset()->usePath()->add('bootstrap-css', 'css/bootstrap.min.css');
            $theme->asset()->usePath()->add('fontawesome-css', 'css/all.min.css');
            $theme->asset()->usePath()->add('animate-css', 'css/animate.css');
            $theme->asset()->usePath()->add('swiper-css', 'css/swiper-bundle.min.css');
            $theme->asset()->usePath()->add('slicknav-css', 'css/slicknav.min.css');
            $theme->asset()->usePath()->add('magnific-css', 'css/magnific-popup.css');
            $theme->asset()->usePath()->add('mousecursor-css', 'css/mousecursor.css');
            $theme->asset()->usePath()->add('owlcarousel-css', 'css/plugins/owlcarousel.min.css');
            $theme->asset()->usePath()->add('sidebar-css', 'css/plugins/sidebar.css');
            $theme->asset()->usePath()->add('slick-slider-css', 'css/plugins/slick-slider.css');
            $theme->asset()->usePath()->add('main-css', 'css/main.css');
            $theme->asset()->usePath()->add('custom-css', 'css/custom.css');

            // CSS plugins
            $theme->asset()->usePath()->add('aos-css', 'css/plugins/aos.css');
            $theme->asset()->usePath()->add('nice-select-css', 'css/plugins/nice-select.css');

            // JS (footer)
            $theme->asset()->container('footer')->usePath()->add('jquery', 'js/jquery-3.7.1.min.js');
            $theme->asset()->container('footer')->usePath()->add('bootstrap-js', 'js/bootstrap.min.js');
            $theme->asset()->container('footer')->usePath()->add('swiper-js', 'js/swiper-bundle.min.js');
            $theme->asset()->container('footer')->usePath()->add('slicknav-js', 'js/jquery.slicknav.js');
            $theme->asset()->container('footer')->usePath()->add('magnific-js', 'js/jquery.magnific-popup.min.js');
            $theme->asset()->container('footer')->usePath()->add('waypoints-js', 'js/jquery.waypoints.min.js');
            $theme->asset()->container('footer')->usePath()->add('counterup-js', 'js/jquery.counterup.min.js');
            $theme->asset()->container('footer')->usePath()->add('wow-js', 'js/wow.min.js');
            $theme->asset()->container('footer')->usePath()->add('aos-js', 'js/plugins/aos.js');
            $theme->asset()->container('footer')->usePath()->add('nice-select-js', 'js/plugins/nice-select.js');
            $theme->asset()->container('footer')->usePath()->add('gsap-js', 'js/gsap.min.js');
            $theme->asset()->container('footer')->usePath()->add('scroll-trigger-js', 'js/ScrollTrigger.min.js');
            $theme->asset()->container('footer')->usePath()->add('split-text-js', 'js/SplitText.js');
            $theme->asset()->container('footer')->usePath()->add('owlcarousel-js', 'js/plugins/owlcarousel.min.js');
            $theme->asset()->container('footer')->usePath()->add('sidebar-js', 'js/plugins/sidebar.js');
            $theme->asset()->container('footer')->usePath()->add('slick-slider-js', 'js/plugins/slick-slider.js');
            $theme->asset()->container('footer')->usePath()->add('main-js', 'js/main.js');
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
            'slicknav' => 'public/css/slicknav.min.css',
            'swiper' => 'public/css/swiper-bundle.min.css',
            'fontawesome' => 'public/css/all.min.css',
            'animate' => 'public/css/animate.css',
            'magnific-popup' => 'public/css/magnific-popup.css',
            'mousecursor' => 'public/css/mousecursor.css',
            'owlcarousel' => 'public/css/plugins/owlcarousel.min.css',
            'sidebar' => 'public/css/plugins/sidebar.css',
            'slick-slider' => 'public/css/plugins/slick-slider.css',

            // Compilados por webpack (assets/sass/ → public/css/)
            'style' => 'public/css/style.css',
            'rtl' => 'public/css/rtl.css',

            // Personalización
            'custom' => 'public/css/custom.css',
        ],
        'js' => [
            // Vendors (ya presentes en public/js/)
            'jquery' => 'public/js/jquery-3.7.1.min.js',
            'bootstrap' => 'public/js/bootstrap.min.js',
            'swiper' => 'public/js/swiper-bundle.min.js',
            'slicknav' => 'public/js/jquery.slicknav.js',
            'magnific-popup' => 'public/js/jquery.magnific-popup.min.js',
            'waypoints' => 'public/js/jquery.waypoints.min.js',
            'wow' => 'public/js/wow.min.js',
            'owlcarousel' => 'public/js/plugins/owlcarousel.min.js',
            'sidebar' => 'public/js/plugins/sidebar.js',
            'slick-slider' => 'public/js/plugins/slick-slider.js',

            // Compilados por webpack (assets/js/ → public/js/)
            'main' => 'public/js/main.js',
            'backend' => 'public/js/backend.js',
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

    /*
    |--------------------------------------------------------------------------
    | Layout Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'responsive_design' => true,
        'rtl_support' => true,
        'dark_mode' => false,
        'mega_menu' => true,
        'product_quick_view' => true,
        'product_comparison' => true,
        'product_wishlist' => true,
        'shopping_cart' => true,
        'ajax_filtering' => true,
        'infinite_scroll' => false,
    ],
];
