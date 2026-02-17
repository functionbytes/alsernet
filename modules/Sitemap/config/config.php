<?php

return [
    'name' => 'Sitemap',

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache_enabled' => true,
    'cache_duration' => 86400,  // 24 horas en segundos

    /*
    |--------------------------------------------------------------------------
    | Sitemap Limits
    |--------------------------------------------------------------------------
    */
    'max_items' => 50000,  // Límite estándar XML Sitemap

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Lista de modelos que serán incluidos en el sitemap
    | Cada modelo debe implementar el trait HasSitemapItems
    */
    'models' => [
        // \Modules\Page\Models\Page::class,
        // \Modules\Post\Models\Post::class,
    ],
];
