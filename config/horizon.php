<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Name
    |--------------------------------------------------------------------------
    */

    'name' => env('HORIZON_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    */

    'waits' => [
        'redis:default' => 60,
        'redis:emails' => 30,
        'redis:exports' => 120,
        'redis:sla' => 30,
        'redis:replies' => 30,
        'redis:helpdesk-scheduled' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    */

    'silenced' => [],

    'silenced_tags' => [],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default', 'notifications'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 90,
            ],
            'supervisor-webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 30,
            ],
            'supervisor-pagespeed' => [
                'connection' => 'redis',
                'queue' => ['pagespeed'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 120,
            ],
            'supervisor-google-sync' => [
                'connection' => 'redis',
                'queue' => ['google-sync'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 120,
            ],
            'reviews-sync' => [
                'connection' => 'redis',
                'queue' => ['reviews-sync'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'tries' => 3,
                'timeout' => 120,
            ],
            'reviews-exports' => [
                'connection' => 'redis',
                'queue' => ['exports'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 300,
            ],
            'reviews-replies' => [
                'connection' => 'redis',
                'queue' => ['reviews-replies', 'replies'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'reviews-notifications' => [
                'connection' => 'redis',
                'queue' => ['notifications', 'emails'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 30,
            ],
            'reviews-webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 15,
            ],
            'supervisor-sla' => [
                'connection' => 'redis',
                'queue' => ['sla'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 1,
                'timeout' => 120,
            ],
            'supervisor-helpdesk' => [
                'connection' => 'redis',
                'queue' => ['helpdesk-scheduled', 'helpdesk-heavy', 'helpdesk-webhooks'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 4,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 300,
            ],
        ],

        'local' => [
            'supervisor-local' => [
                'connection' => 'redis',
                'queue' => ['default', 'webhooks', 'pagespeed', 'google-sync', 'notifications', 'reviews-sync', 'exports', 'reviews-replies', 'replies', 'emails', 'sla', 'helpdesk-scheduled', 'helpdesk-heavy', 'helpdesk-webhooks'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 1,
                'timeout' => 60,
            ],
        ],
    ],
];
