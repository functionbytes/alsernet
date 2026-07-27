<?php

return [
    'name' => 'Engagement',

    /*
    |--------------------------------------------------------------------------
    | Branding (white-label support)
    |--------------------------------------------------------------------------
    | Used everywhere user-facing text mentions the product. Set in .env:
    |   BRAND_NAME="Alsernet"
    |   BRAND_SLUG="alsernet_chat"
    |   BRAND_URL="https://alsernet.com"
    */
    'brand' => [
        'name' => env('BRAND_NAME', 'Engagement'),
        'slug' => env('BRAND_SLUG', 'engagement_chat'),
        'url' => env('BRAND_URL', config('app.url', 'https://example.com')),
    ],

    /*
    |--------------------------------------------------------------------------
    | ML Prediction Service
    |--------------------------------------------------------------------------
    | Optional remote microservice for conversion/next-action predictions.
    | When unset, MlPredictorService falls back to its local heuristic.
    */
    'ml_service_url' => env('ENGAGEMENT_ML_SERVICE_URL'),
];
