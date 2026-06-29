<?php

return [
    'name' => 'HelpdeskHelpcenter',

    /*
    |--------------------------------------------------------------------------
    | Supported locales for article translations
    |--------------------------------------------------------------------------
    | Used by ArticleTranslationsController and the public-facing locale resolver.
    */
    'supported_locales' => [
        'es', 'en', 'es-MX', 'pt', 'fr', 'de', 'it',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'managers' => (int) env('HELPCENTER_PAGINATE_MANAGERS', 20),
        'public' => (int) env('HELPCENTER_PAGINATE_PUBLIC', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public listing
    |--------------------------------------------------------------------------
    */
    'public' => [
        'related_limit' => (int) env('HELPCENTER_RELATED_LIMIT', 5),
        'search_min_chars' => (int) env('HELPCENTER_SEARCH_MIN_CHARS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Votes
    |--------------------------------------------------------------------------
    | Salt used to hash voter IP addresses. Falls back to APP_KEY when unset.
    */
    'vote_ip_salt' => env('HELPCENTER_VOTE_IP_SALT'),
];
