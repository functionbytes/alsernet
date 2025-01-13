<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sanctum Authentication Guard
    |--------------------------------------------------------------------------
    |
    | This value controls the authentication guard that Sanctum uses while
    | authenticating incoming requests. You may change this if you have
    | a custom guard for API authentication.
    |
    */

    'guard' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Statefully Scoped Domains
    |--------------------------------------------------------------------------
    |
    | Sanctum allows you to specify which domains are stateful. This allows
    | you to control which domains should be able to authenticate users
    | using Sanctum cookies. This is useful when using multiple subdomains.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,example.com')),

    /*
    |--------------------------------------------------------------------------
    | Expiration Time For Tokens
    |--------------------------------------------------------------------------
    |
    | You can define the expiration time for your personal access tokens issued
    | by Sanctum. If you don't want tokens to expire, set the value to null.
    |
    */

    'expiration' => null, // Set to null for no expiration

    /*
    |--------------------------------------------------------------------------
    | Sanctum Cookie Name
    |--------------------------------------------------------------------------
    |
    | When using the "stateful" driver, Sanctum needs a cookie name to issue
    | when it sends the user information to the browser. You may set this to
    | a custom value if desired.
    |
    */

    'cookie' => 'XSRF-TOKEN',

    /*
    |--------------------------------------------------------------------------
    | Sanctum Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum will prefix all of its token URLs with this value. You may change
    | this if you prefer a different URL prefix for Sanctum's endpoints.
    |
    */

    'prefix' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Sanctum uses Laravel's built-in rate limiting feature to limit the number
    | of requests that a user can make in a given period of time. You can
    | adjust this limit as needed to prevent abuse of your API.
    |
    */

    'limit' => 60, // Requests per minute

    'minutes' => 1, // Time window for rate limiting

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be applied to all routes that require Sanctum authentication.
    | Make sure you have the necessary middleware for CSRF and encryption.
    |
    */

    'middleware' => [
        'verify_csrf_token' => \App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => \App\Http\Middleware\EncryptCookies::class,
        'web',
        'auth:sanctum',
    ],
];
