<?php

return [
    /*
     * The default queue connection to use.
     */
    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
     * Queue connections configuration.
     */
    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    /*
     * Failed job storing configuration.
     */
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
     * Rate limiting configuration for jobs.
     */
    'rate_limited' => [
        'enabled' => true,
        'per_minute' => 10,
    ],
];
