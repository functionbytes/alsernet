<?php

use Modules\Blog\Models\BlogPost;
use Modules\Page\Models\Page;

return [
    'cache_enabled' => true,
    'cache_duration' => 86400,
    'max_items' => 50000,

    // Sitemap principal (/sitemap.xml)
    'models' => [
        Page::class,
        BlogPost::class,
    ],

    // /sitemap-pages.xml
    'page_models' => [
        Page::class,
    ],

    // /sitemap-posts.xml  (BlogCategory/BlogTag via post_callbacks en SeoServiceProvider)
    'post_models' => [
        BlogPost::class,
    ],

    // Registrados dinámicamente por SeoServiceProvider::registerSitemapCallbacks()
    'post_callbacks' => [],
];
