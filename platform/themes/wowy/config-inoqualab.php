<?php

/**
 * Configuración de Plantilla Wowy para inoqualab
 *
 * Esta plantilla requiere que el módulo Ecommerce esté activo.
 * Soporta múltiples layouts para diferentes tipos de contenido.
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
        'css' => [
            // Vendors
            'normalize' => 'public/css/vendors/normalize.css',
            'bootstrap' => 'public/plugins/bootstrap/css/bootstrap.min.css',
            'fontawesome' => 'public/plugins/fontawesome/css/all.css',
            'animate' => 'public/css/plugins/animate.css',
            'slick' => 'public/css/plugins/slick.css',
            'wowy-font' => 'public/css/vendors/wowy-font.css',

            // Main styles
            'style' => 'public/css/style.css',
            'rtl' => 'public/css/rtl.css', // Para soporte RTL
        ],
        'js' => [
            // Vendors
            'modernizr' => 'public/js/vendor/modernizr-3.6.0.min.js',
            'jquery' => 'public/js/vendor/jquery.min.js',
            'jquery-migrate' => 'public/js/vendor/jquery-migrate.min.js',
            'bootstrap' => 'public/plugins/bootstrap/js/bootstrap.bundle.min.js',
            'slick' => 'public/js/plugins/slick.js',
            'syotimer' => 'public/js/plugins/jquery.syotimer.min.js',
            'wow' => 'public/js/plugins/wow.js',
            'waypoints' => 'public/js/plugins/waypoints.js',
            'countdown' => 'public/js/plugins/jquery.countdown.min.js',
            'vticker' => 'public/js/plugins/jquery.vticker-min.js',

            // Main scripts
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
