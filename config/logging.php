<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        // Canal específico para devoluciones
        'returns' => [
            'driver' => 'daily',
            'path' => storage_path('logs/returns/returns.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'formatter' => App\Logging\ReturnLogFormatter::class,
        ],

        // Canal para métricas de negocio
        'metrics' => [
            'driver' => 'daily',
            'path' => storage_path('logs/metrics/business-metrics.log'),
            'level' => 'info',
            'days' => 90,
            'formatter' => App\Logging\MetricsLogFormatter::class,
        ],

        // Canal para pagos
        'payments' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payments/payments.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 365, // Retener logs de pagos por 1 año
            'formatter' => App\Logging\PaymentLogFormatter::class,
        ],

        // Canal para auditoría
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit/audit.log'),
            'level' => 'info',
            'days' => 365,
            'formatter' => App\Logging\AuditLogFormatter::class,
        ],

        // Canal para errores críticos
        'critical' => [
            'driver' => 'stack',
            'channels' => ['critical-file', 'slack-critical'],
        ],

        'critical-file' => [
            'driver' => 'daily',
            'path' => storage_path('logs/critical/critical.log'),
            'level' => 'critical',
            'days' => 90,
        ],

        // Canal para Slack (errores críticos)
        'slack-critical' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        // Canal para performance monitoring
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance/performance.log'),
            'level' => 'info',
            'days' => 30,
            'formatter' => App\Logging\PerformanceLogFormatter::class,
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => Monolog\Handler\StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
