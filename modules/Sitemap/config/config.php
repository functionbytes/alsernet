<?php

return [
    'name' => 'Sitemap',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache_enabled' => true,
    'cache_duration' => 86400, // segundos (24h)

    /*
    |--------------------------------------------------------------------------
    | Limite de items por archivo (estándar XML Sitemap: 50,000)
    |--------------------------------------------------------------------------
    */
    'max_items' => 50000,

    /*
    |--------------------------------------------------------------------------
    | URLs estáticas
    | Agregadas siempre al sitemap principal, sin depender de modelos.
    |
    | Ejemplo:
    | ['loc' => '/nosotros', 'priority' => '0.7', 'changefreq' => 'monthly'],
    |--------------------------------------------------------------------------
    */
    'static_urls' => [
        // ['loc' => '/nosotros', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modelos — sitemap principal (/sitemap.xml)
    | Cada modelo debe usar el trait HasSitemapItems.
    |--------------------------------------------------------------------------
    */
    'models' => [
        // \Modules\Page\Models\Page::class,
        // \Modules\Post\Models\Post::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Modelos por categoría — sitemaps secundarios
    |--------------------------------------------------------------------------
    */
    'page_models' => [
        // \Modules\Page\Models\Page::class,
    ],

    'post_models' => [
        // \Modules\Post\Models\Post::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemaps adicionales para el índice (/sitemap-index.xml)
    | Otros módulos pueden añadir sus URLs aquí via mergeConfigFrom o booted().
    | Ejemplo: url('/sitemap-images.xml')
    |--------------------------------------------------------------------------
    */
    'index_extra' => [],

    /*
    |--------------------------------------------------------------------------
    | Callbacks para /sitemap-posts.xml
    | Registrados por otros módulos para añadir modelos sin HasSitemapItems.
    |--------------------------------------------------------------------------
    */
    'post_callbacks' => [],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt
    | Cada bloque genera un grupo User-agent con sus Disallow/Allow opcionales.
    |--------------------------------------------------------------------------
    */
    'robots' => [
        [
            'user_agent' => '*',
            'disallow' => ['/panel/', '/api/'],
            'allow' => ['/'],
        ],
    ],
];
