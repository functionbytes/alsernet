<?php

return [
    'version' => env('API_VERSION', 'v1'),

    'rate_limits' => [
        'public' => env('API_RATE_LIMIT_PUBLIC', 20),
        'authenticated' => env('API_RATE_LIMIT_AUTH', 100),
        'admin' => env('API_RATE_LIMIT_ADMIN', 500),
    ],

    'pagination' => [
        'default_per_page' => env('API_PER_PAGE', 15),
        'max_per_page' => env('API_MAX_PER_PAGE', 100),
    ],
];
