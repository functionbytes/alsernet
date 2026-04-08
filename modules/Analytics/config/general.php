<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Cache Lifetime
    |--------------------------------------------------------------------------
    |
    | The cache lifetime in minutes for analytics data.
    | Default: 60 minutes (1 hour)
    |
    */
    'cache_lifetime' => env('ANALYTICS_CACHE_LIFETIME', 60),

    /*
    |--------------------------------------------------------------------------
    | Default Date Range
    |--------------------------------------------------------------------------
    |
    | The default date range to use when fetching analytics data.
    | Options: today, yesterday, last_7_days, last_30_days, this_month, last_month, this_year
    |
    */
    'default_date_range' => env('ANALYTICS_DEFAULT_DATE_RANGE', 'last_7_days'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics dashboard widgets.
    |
    */
    'widgets' => [
        'enabled' => [
            'general',
            'top_pages',
            'top_browsers',
            'top_referrers',
        ],
        'refresh_interval' => 300, // 5 minutes in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Configurations
    |--------------------------------------------------------------------------
    |
    | Specific configuration for each widget type.
    |
    */
    'widget_config' => [
        'general' => [
            'metrics' => ['sessions', 'totalUsers', 'screenPageViews', 'bounceRate'],
            'cache_key' => 'analytics.widget.general',
        ],
        'top_pages' => [
            'limit' => 10,
            'metrics' => ['screenPageViews'],
            'dimensions' => ['pageTitle', 'fullPageUrl'],
            'cache_key' => 'analytics.widget.top_pages',
        ],
        'top_browsers' => [
            'limit' => 10,
            'metrics' => ['sessions'],
            'dimensions' => ['browser'],
            'cache_key' => 'analytics.widget.top_browsers',
        ],
        'top_referrers' => [
            'limit' => 10,
            'metrics' => ['screenPageViews'],
            'dimensions' => ['sessionSource'],
            'cache_key' => 'analytics.widget.top_referrers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Metrics
    |--------------------------------------------------------------------------
    |
    | All available Google Analytics 4 metrics that can be queried.
    |
    */
    'available_metrics' => [
        'sessions',
        'totalUsers',
        'newUsers',
        'screenPageViews',
        'bounceRate',
        'engagementRate',
        'averageSessionDuration',
        'conversions',
        'eventCount',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Dimensions
    |--------------------------------------------------------------------------
    |
    | All available Google Analytics 4 dimensions that can be queried.
    |
    */
    'available_dimensions' => [
        'date',
        'pageTitle',
        'fullPageUrl',
        'pagePath',
        'browser',
        'country',
        'city',
        'sessionSource',
        'sessionMedium',
        'deviceCategory',
        'operatingSystem',
        'language',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Range Presets
    |--------------------------------------------------------------------------
    |
    | Predefined date range options for quick selection.
    |
    */
    'date_ranges' => [
        'today' => [
            'label' => 'Hoy',
            'days' => 1,
        ],
        'yesterday' => [
            'label' => 'Ayer',
            'days' => 1,
        ],
        'last_7_days' => [
            'label' => 'Últimos 7 días',
            'days' => 7,
        ],
        'last_30_days' => [
            'label' => 'Últimos 30 días',
            'days' => 30,
        ],
        'this_month' => [
            'label' => 'Este mes',
            'days' => null,
        ],
        'last_month' => [
            'label' => 'Mes pasado',
            'days' => null,
        ],
        'this_year' => [
            'label' => 'Este año',
            'days' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Limits
    |--------------------------------------------------------------------------
    |
    | Limits for analytics queries to prevent performance issues.
    |
    */
    'query_limits' => [
        'max_results' => 100,
        'default_limit' => 20,
        'max_date_range_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Google Analytics API configuration.
    |
    */
    'api' => [
        'timeout' => 30, // seconds
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
        'throttle_per_minute' => env('ANALYTICS_THROTTLE_PER_MINUTE', 60),
    ],
];
