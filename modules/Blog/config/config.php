<?php

return [
    'name' => 'Blog',
    'posts_per_page' => 12,
    'allow_comments' => true,
    'default_status' => 'draft',
    'slug_separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | Locales available for translation (excluding the primary locale 'es').
    | Can be overridden via the database setting 'blog-supported-locales'.
    */
    'supported_locales' => explode(',', env('BLOG_SUPPORTED_LOCALES', 'en,pt')),
];
