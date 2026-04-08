<?php

use Modules\Page\Models\Page;

return [
    'cache_enabled' => true,
    'cache_duration' => 86400,
    'max_items' => 50000,
    'models' => [
        Page::class,
        // \Modules\Post\Models\Post::class,
    ],
];
