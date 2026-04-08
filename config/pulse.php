<?php

// Completely disable Pulse when the module is disabled
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\CacheInteractions;
use Laravel\Pulse\Recorders\Exceptions;
use Laravel\Pulse\Recorders\Queues;
use Laravel\Pulse\Recorders\Servers;
use Laravel\Pulse\Recorders\SlowJobs;
use Laravel\Pulse\Recorders\SlowOutgoingRequests;
use Laravel\Pulse\Recorders\SlowQueries;
use Laravel\Pulse\Recorders\SlowRequests;
use Nwidart\Modules\Facades\Module;

$pulseEnabled = true;
try {
    $module = Module::find('Pulse');
    $pulseEnabled = ! $module?->isDisabled();
} catch (Exception $e) {
    // Fallback to true if module check fails
}

return [
    'enabled' => $pulseEnabled,
    'path' => env('PULSE_PATH', 'pulse'),
    'domain' => env('PULSE_DOMAIN'),
    'middleware' => [],
    'recorders' => [
        CacheInteractions::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_CACHE_INTERACTIONS_SAMPLE_RATE', 1),
            'ignore' => [
                ...Pulse::defaultVendorCacheKeys(),
            ],
            'groups' => [
                '/^job-exceptions:.*/' => 'job-exceptions:*',
            ],
        ],

        Exceptions::class => [
            'enabled' => env('PULSE_EXCEPTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_EXCEPTIONS_SAMPLE_RATE', 1),
            'location' => env('PULSE_EXCEPTIONS_LOCATION', true),
            'ignore' => [],
        ],

        Queues::class => [
            'enabled' => env('PULSE_QUEUES_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1),
            'ignore' => [],
        ],

        Servers::class => [
            'server_name' => env('PULSE_SERVER_NAME', gethostname()),
            'directories' => explode(':', env('PULSE_SERVER_DIRECTORIES', '/')),
        ],

        SlowJobs::class => [
            'enabled' => env('PULSE_SLOW_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_JOBS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_JOBS_THRESHOLD', 1000),
            'ignore' => [],
        ],

        SlowOutgoingRequests::class => [
            'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
            'ignore' => [],
            'groups' => [],
        ],

        SlowQueries::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_QUERIES_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_QUERIES_THRESHOLD', 1000),
            'location' => env('PULSE_SLOW_QUERIES_LOCATION', true),
            'max_query_length' => env('PULSE_SLOW_QUERIES_MAX_QUERY_LENGTH'),
            'ignore' => [
                '/(["`])pulse_[\w]+?\1/',
                '/(["`])telescope_[\w]+?\1/',
            ],
        ],

        SlowRequests::class => [
            'enabled' => env('PULSE_SLOW_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_REQUESTS_SAMPLE_RATE', 1),
            'threshold' => env('PULSE_SLOW_REQUESTS_THRESHOLD', 1000),
            'ignore' => [
                '#^/'.env('PULSE_PATH', 'pulse').'$#',
                '#^/telescope#',
            ],
        ],
    ],
    'storage' => [
        'driver' => $pulseEnabled ? env('PULSE_STORAGE_DRIVER', 'database') : null,
        'trim' => ['keep' => env('PULSE_STORAGE_KEEP', '7 days')],
        'database' => ['connection' => env('PULSE_DB_CONNECTION'), 'chunk' => 1000],
    ],
    'ingest' => [
        'driver' => $pulseEnabled ? env('PULSE_INGEST_DRIVER', 'storage') : null,
        'buffer' => env('PULSE_INGEST_BUFFER', 5_000),
        'trim' => ['lottery' => [1, 1_000], 'keep' => env('PULSE_INGEST_KEEP', '7 days')],
        'redis' => ['connection' => env('PULSE_REDIS_CONNECTION'), 'chunk' => 1000],
    ],
];
