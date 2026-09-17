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
        'redis:helpdesk-ai' => 120,
        'redis:remarketing' => 60,
        'redis:remarketing-webhooks' => 15,
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

    'memory_limit' => 256,

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
                'queue' => ['default', 'notifications', 'notifications-high'],
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
                'queue' => ['webhooks', 'helpdesk-webhooks'],
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
            'supervisor-helpdesk-webhooks' => [
                'connection' => 'redis',
                'queue' => ['helpdesk-webhooks'],
                'balance' => 'simple',
                'minProcesses' => 3,
                'maxProcesses' => 8,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-helpdesk' => [
                'connection' => 'redis',
                // helpdesk-audit sumada despues de este config original
                // (RecordTicketHistory, HelpdeskTickets): sin worker propio
                // caia al default de Horizon y nunca se procesaba. Mismo caso
                // con las 3 colas helpdesk-social-* (HelpdeskSocial, activado
                // 18-sep-2026): sin worker propio tambien caian al default.
                'queue' => ['helpdesk', 'helpdesk-events', 'helpdesk-scheduled', 'helpdesk-heavy', 'helpdesk-ai', 'helpdesk-audit', 'helpdesk-social-ai', 'helpdesk-social-analytics', 'helpdesk-social-processing', 'chatflow'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 4,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 300,
            ],
            'supervisor-livechat' => [
                'connection' => 'redis',
                // helpdesk-broadcasts sumada 18-sep-2026 (QA tiempo real): la usan
                // ~47 eventos ShouldBroadcast del inbox via el trait
                // BroadcastsOnServedQueue (App\Events\Concerns), pero ningun
                // supervisor la escuchaba — mismo patron que las colas de arriba
                // (helpdesk-audit, helpdesk-social-*): sin worker dedicado, caian
                // al 'default' de Horizon (que tampoco la lista) y se quedaban
                // encoladas para siempre. Se detecto porque ConversationUpdated
                // (estado/prioridad/agente/equipo) nunca llegaba en vivo a un
                // segundo agente con la misma conversacion abierta — 42 jobs
                // acumulados en Redis en el momento del hallazgo. Va en esta
                // cola por ser la mas ligera/realtime, tal como pide el propio
                // comentario del trait ("no debe compartir sitio con envios
                // masivos").
                'queue' => ['helpdesk-broadcasts', 'helpdesklivechat'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 6,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-broadcasts' => [
                'connection' => 'redis',
                'queue' => ['broadcasts'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 1,
                'timeout' => 360,
                'nice' => 0,
            ],
            'supervisor-drip' => [
                'connection' => 'redis',
                'queue' => ['drip'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 4,
                'tries' => 3,
                'timeout' => 120,
                'nice' => 0,
            ],
            'supervisor-remarketing' => [
                'connection' => 'redis',
                'queue' => ['remarketing'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 2,
                'maxProcesses' => 8,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 120,
                'memory' => 256,
            ],
            'supervisor-remarketing-webhooks' => [
                'connection' => 'redis',
                'queue' => ['remarketing-webhooks'],
                'balance' => 'simple',
                'processes' => 4,
                'tries' => 5,
                'timeout' => 30,
                'memory' => 128,
            ],
        ],

        'local' => [
            'supervisor-local-webhooks' => [
                'connection' => 'redis',
                'queue' => ['helpdesk-webhooks', 'webhooks'],
                'balance' => 'simple',
                'processes' => 5,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-local' => [
                'connection' => 'redis',
                // helpdesk-broadcasts y helpdesklivechat sumadas 18-sep-2026 (QA
                // tiempo real): este es el bloque de entorno realmente activo en
                // el Docker de dev (APP_ENV=local) — el bloque 'production' de
                // arriba con sus supervisores dedicados no aplica aqui. Sin
                // 'helpdesk-broadcasts' en esta lista, eventos como
                // ConversationUpdated (estado/prioridad/agente/equipo del inbox)
                // se encolaban y jamas se procesaban: 42 jobs acumulados en Redis
                // en el momento del hallazgo, cero en el log de Horizon. Mismo
                // caso con 'helpdesklivechat', que tampoco estaba.
                'queue' => ['default', 'pagespeed', 'google-sync', 'notifications', 'notifications-high', 'reviews-sync', 'exports', 'reviews-replies', 'replies', 'emails', 'sla', 'helpdesk', 'helpdesk-events', 'helpdesk-scheduled', 'helpdesk-heavy', 'helpdesk-ai', 'helpdesk-audit', 'helpdesk-social-ai', 'helpdesk-social-analytics', 'helpdesk-social-processing', 'chatflow', 'broadcasts', 'helpdesk-broadcasts', 'helpdesklivechat', 'drip', 'remarketing', 'remarketing-webhooks'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 1,
                'timeout' => 60,
            ],
        ],
    ],
];
