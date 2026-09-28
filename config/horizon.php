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

            // ---------------------------------------------------------------
            // A partir de aca: colas propias de este proyecto (webadmin, NO
            // webadminpruebas -- ese vive en su propio config/horizon.php,
            // archivo aparte) que hoy procesan supervisores sueltos de
            // /etc/supervisor/conf.d/ y no tenian entrada en Horizon. Se
            // agregan 18-sep-2026 para poder migrarlos a Horizon; mismo
            // tuning (tries/timeout/procesos) que su supervisor equivalente,
            // para que el comportamiento no cambie al migrar. Los tres que
            // corren con conexion `database` (health, document-default,
            // document-emails) NO pueden entrar aca -- Horizon es Redis-only,
            // se quedan como supervisores propios para siempre.
            'supervisor-erp' => [
                'connection' => 'redis',
                'queue' => ['erp'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 1,
                'timeout' => 60,
            ],
            'supervisor-helpdesk-erp' => [
                'connection' => 'redis',
                'queue' => ['helpdesk-erp', 'helpdesk-erp-warming', 'helpdesk-ps', 'helpdesk-ps-warming', 'helpdesk-embeddings'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 120,
            ],
            'supervisor-helpdesk-compliance' => [
                'connection' => 'redis',
                'queue' => ['helpdeskcompliance'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 60,
            ],
            'supervisor-campaigns' => [
                'connection' => 'redis',
                'queue' => ['campaigns-scheduler', 'impressions'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 60,
            ],
            // Los supervisores con 'redis-long' (supplier-sync, -maintenance, -ai y
            // media-heavy) atienden colas con jobs de mas de 6 minutos: ver
            // config/queue.php. No cambiar su conexion a 'redis' sin subir antes
            // REDIS_QUEUE_RETRY_AFTER por encima de su timeout.
            'supervisor-supplier-sync' => [
                'connection' => 'redis-long',
                'queue' => ['sync'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 3700,
            ],
            'supervisor-supplier-extraction' => [
                'connection' => 'redis',
                'queue' => ['supplier-extraction'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 310,
            ],
            'supervisor-supplier-retry' => [
                'connection' => 'redis',
                'queue' => ['supplier-retry'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 310,
            ],
            'supervisor-supplier-maintenance' => [
                'connection' => 'redis-long',
                'queue' => ['maintenance'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 1,
                'timeout' => 3700,
            ],
            'supervisor-supplier-ai' => [
                'connection' => 'redis-long',
                'queue' => ['ai-generation', 'ai-content-generation'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 700,
            ],
            // Procesado de adjuntos/medios (modulo Media) -- agregado 21-sep-2026:
            // ninguna de estas colas tenia supervisor en produccion, asi que
            // miniaturas, WebP, limpieza EXIF, optimizacion y escaneo antivirus
            // de lo que se sube (adjuntos de tickets y conversaciones incluidos)
            // se encolaban en Redis y no se ejecutaban nunca. 'timeout' 310 por
            // encima del $timeout mas alto de sus jobs (ScanForVirusJob, 300) y
            // por debajo de retry_after (360), como supplier-extraction.
            // media-heavy (OCR, marcas de agua, HLS...) va aparte para que un
            // video largo no bloquee las miniaturas; TranscodeToHlsJob declara
            // $timeout 1800 > retry_after, revisar REDIS_QUEUE_RETRY_AFTER si
            // se usa (mismo caso que supplier-sync).
            'supervisor-media' => [
                'connection' => 'redis',
                'queue' => ['media-light', 'media-optimize', 'media-scan'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 310,
            ],
            'supervisor-media-heavy' => [
                'connection' => 'redis-long',
                'queue' => ['media-heavy'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 2,
                'timeout' => 310,
            ],
            // SyncContentToPrestashopJob ($timeout 300) -- tampoco tenia worker.
            'supervisor-prestashop-sync' => [
                'connection' => 'redis',
                'queue' => ['prestashop-sync'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 310,
            ],
            // Cupones de cumpleanos (HelpdeskBirthday, SendBirthdayEmailJob) --
            // agregada 18-sep-2026: nunca tuvo worker real escuchandola, ni
            // antes de esta migracion (config('helpdeskbirthday.queue',
            // 'birthdays'), verificado que ningun supervisor la cubria, ni
            // el instalado en produccion ni la plantilla vieja del repo).
            'supervisor-birthdays' => [
                'connection' => 'redis',
                'queue' => ['birthdays'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 60,
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
                //
                // helpdesk-ps, helpdesk-ps-warming, helpdesk-erp,
                // helpdesk-erp-warming y helpdesk-embeddings sumadas 20-sep-2026:
                // mismo patron -- en 'production' las cubre 'supervisor-helpdesk-erp'
                // (linea 353), pero aqui no habia worker. RefreshPsContextJob
                // (revalidacion en background del contexto de cliente de
                // PrestashopContextService) y WarmPsCacheJob (programado cada 30
                // min) se encolaban y jamas se procesaban en local.
                //
                // media-light/optimize/scan, helpdeskcompliance, impressions y
                // campaigns-scheduler sumadas 21-sep-2026: eran colas con jobs
                // reales sin worker (66 de Media y 1.666 PublishScheduled/
                // EndExpiredCampaignsJob acumulados en Redis; el borrado GDPR
                // en cascada de HelpdeskCompliance tampoco corria). Se dejan
                // FUERA a proposito las que escriben en sistemas externos --
                // sync (SyncProductToErpListener escribe en el ERP),
                // prestashop-sync, supplier-*, ai-content-generation -- y
                // media-heavy (OCR/IA/HLS): en local no debe salir nada hacia
                // fuera sin quererlo. En produccion si estan cubiertas.
                'queue' => ['default', 'pagespeed', 'google-sync', 'notifications', 'notifications-high', 'reviews-sync', 'exports', 'reviews-replies', 'replies', 'emails', 'sla', 'helpdesk', 'helpdesk-events', 'helpdesk-scheduled', 'helpdesk-heavy', 'helpdesk-ai', 'helpdesk-audit', 'helpdesk-social-ai', 'helpdesk-social-analytics', 'helpdesk-social-processing', 'chatflow', 'broadcasts', 'helpdesk-broadcasts', 'helpdesklivechat', 'drip', 'remarketing', 'remarketing-webhooks', 'helpdesk-ps', 'helpdesk-ps-warming', 'helpdesk-erp', 'helpdesk-erp-warming', 'helpdesk-embeddings', 'media-light', 'media-optimize', 'media-scan', 'helpdeskcompliance', 'impressions', 'campaigns-scheduler'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 1,
                'timeout' => 60,
            ],
        ],
    ],
];
