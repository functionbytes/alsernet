<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Name
    |--------------------------------------------------------------------------
    |
    | This name appears in notifications and in the Horizon UI. Unique names
    | can be useful while running multiple instances of Horizon within an
    | application, allowing you to identify the Horizon you're viewing.
    |
    */

    'name' => env('HORIZON_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
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
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to fully remove any noisy jobs from the completed jobs list.
    |
    */

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'silenced_tags' => [
        // 'notifications',
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
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
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    // El valor de fábrica (64) lo excedía nada más arrancar: esta app carga
    // 40 módulos (nwidart/laravel-modules) en el bootstrap, y el proceso
    // maestro de Horizon (no los workers, esto es aparte) ya iba a 66 MB
    // recién levantado — Horizon lo mataba y reiniciaba en bucle sin
    // ningún error visible más que "Memory limit exceeded" en el log
    // (confirmado en vivo, 14-sep-2026).
    'memory_limit' => 256,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Supervisores — calcados 1:1 de docker/docker-compose.yml (14-sep-2026)
    |--------------------------------------------------------------------------
    |
    | Hoy hay 3 contenedores, cada uno un `queue:work` fijo a su propia lista
    | de colas — NO un supervisor por cola una a una, sino por CARACTERÍSTICA
    | (tiempo real / fondo / catch-all), a propósito: un job lento de IA o de
    | sincronización con el ERP en la misma cola que un mensaje real de chat
    | lo retrasaba (ver el comentario junto a `worker-helpdesk-realtime` en el
    | compose, incidente del mensaje de bienvenida, 7-ago-2026). Esta
    | configuración reproduce exactamente esa misma separación:
    |
    |   worker                    -> supervisor-default      (catch-all/fondo)
    |   worker-helpdesk           -> supervisor-helpdesk      (fondo Helpdesk)
    |   worker-helpdesk-realtime  -> supervisor-helpdesk-realtime (tiempo real)
    |
    | IMPORTANTE antes de apagar los contenedores actuales: 'default' y
    | 'sync' ya tuvieron colas sin worker con decenas/cientos de miles de
    | jobs acumulados (145.107 en 'default', 92.148 en 'notifications-high'
    | — ver memoria del proyecto, 7-sep-2026 y 10-ago-2026). Antes de
    | arrancar `php artisan horizon` en vez de los 3 contenedores, revisa el
    | tamaño real de cada cola (`redis-cli llen <cola>` o el propio
    | dashboard de Horizon tras arrancarlo) — no asumas que están vacías.
    |
    */

    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            // default,sync,exports — mismo orden y colas que
            // `command: ... --queue=default,sync,exports` del servicio
            // `worker`.
            'queue' => ['default', 'sync', 'exports'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            // --max-jobs=500 del compose no tiene equivalente 1:1 en
            // Horizon (que recicla el worker con maxTime/memory en su
            // lugar) — se deja en 0 (sin límite de jobs) a propósito.
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 0,
        ],
        'supervisor-helpdesk' => [
            'connection' => 'redis',
            // Lista larga a propósito, calcada del servicio `worker-helpdesk`:
            // todo lo de FONDO de Helpdesk (IA/embeddings/ERP/PS warming,
            // social, compliance) + notificaciones/marketing/emails masivos.
            // Comparte worker porque ninguna de estas es sensible a la
            // latencia — lo que NO puede compartir worker con esto es la
            // cola 'helpdesk' de mensajes reales, ver supervisor-helpdesk-
            // realtime más abajo.
            'queue' => [
                'helpdesk-scheduled', 'helpdesk-ai', 'helpdesk-embeddings',
                'helpdesk-erp', 'helpdesk-erp-warming', 'helpdesk-ps', 'helpdesk-ps-warming',
                'helpdesk-audit', 'helpdesk-social-ai', 'helpdesk-social-analytics', 'helpdesk-social-processing',
                'helpdeskcompliance', 'notifications', 'impressions', 'webhooks',
                'campaigns-scheduler', 'chatflow', 'drip', 'emails', 'birthdays', 'helpdesklivechat',
            ],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 0,
        ],
        'supervisor-helpdesk-realtime' => [
            'connection' => 'redis',
            // De cara al cliente: ingesta de mensajes entrantes, eventos y
            // broadcasts de la conversación, notificaciones de alta
            // prioridad. timeout=60 (no 300 como los otros dos) a propósito
            // — mismo valor que `worker-helpdesk-realtime` en el compose:
            // un job aquí que tarde más de un minuto es una señal de que
            // algo va mal, no un trabajo pesado legítimo como en las otras
            // colas.
            'queue' => ['helpdesk-webhooks', 'helpdesk', 'helpdesk-events', 'helpdesk-broadcasts', 'broadcasts', 'notifications-high'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            // Los 3 arrancan con 1 proceso hoy (un contenedor cada uno, sin
            // réplicas) — en producción se les deja escalar de verdad, con
            // el realtime como prioridad (más proceso mínimo, para no
            // depender de que el autoscaling reaccione a tiempo ante un
            // pico de mensajes).
            'supervisor-default' => [
                'maxProcesses' => 6,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-helpdesk' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-helpdesk-realtime' => [
                'minProcesses' => 2,
                'maxProcesses' => 10,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 1,
            ],
        ],

        'local' => [
            // Mismo baseline que los 3 contenedores actuales (1 proceso
            // cada uno) — sin autoescalar de más en el equipo de
            // desarrollo.
            'supervisor-default' => [
                'maxProcesses' => 1,
            ],
            'supervisor-helpdesk' => [
                'maxProcesses' => 2,
            ],
            'supervisor-helpdesk-realtime' => [
                'maxProcesses' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watcher Configuration
    |--------------------------------------------------------------------------
    |
    | The following list of directories and files will be watched when using
    | the `horizon:listen` command. Whenever any directories or files are
    | changed, Horizon will automatically restart to apply all changes.
    |
    */

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        'composer.json',
        '.env',
    ],
];
