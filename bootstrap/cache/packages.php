<?php

return [
    'aerni/cloudflared' => [
        'providers' => [
            0 => 'Aerni\\Cloudflared\\CloudflaredServiceProvider',
        ],
    ],
    'ashallendesign/email-utilities' => [
        'providers' => [
            0 => 'AshAllenDesign\\EmailUtilities\\EmailUtilitiesProvider',
        ],
    ],
    'ashallendesign/laravel-config-validator' => [
        'aliases' => [
            'ConfigValidator' => 'AshAllenDesign\\ConfigValidator\\Facades\\ConfigValidator',
        ],
        'providers' => [
            0 => 'AshAllenDesign\\ConfigValidator\\Providers\\ConfigValidatorProvider',
        ],
    ],
    'barryvdh/laravel-debugbar' => [
        'aliases' => [
            'Debugbar' => 'Fruitcake\\LaravelDebugbar\\Facades\\Debugbar',
        ],
        'providers' => [
            0 => 'Fruitcake\\LaravelDebugbar\\ServiceProvider',
        ],
    ],
    'barryvdh/laravel-dompdf' => [
        'aliases' => [
            'PDF' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
            'Pdf' => 'Barryvdh\\DomPDF\\Facade\\Pdf',
        ],
        'providers' => [
            0 => 'Barryvdh\\DomPDF\\ServiceProvider',
        ],
    ],
    'barryvdh/laravel-ide-helper' => [
        'providers' => [
            0 => 'Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider',
        ],
    ],
    'barryvdh/laravel-translation-manager' => [
        'providers' => [
            0 => 'Barryvdh\\TranslationManager\\ManagerServiceProvider',
        ],
    ],
    'laravel/boost' => [
        'providers' => [
            0 => 'Laravel\\Boost\\BoostServiceProvider',
        ],
    ],
    'laravel/horizon' => [
        'aliases' => [
            'Horizon' => 'Laravel\\Horizon\\Horizon',
        ],
        'providers' => [
            0 => 'Laravel\\Horizon\\HorizonServiceProvider',
        ],
    ],
    'laravel/mcp' => [
        'aliases' => [
            'Mcp' => 'Laravel\\Mcp\\Server\\Facades\\Mcp',
        ],
        'providers' => [
            0 => 'Laravel\\Mcp\\Server\\McpServiceProvider',
        ],
    ],
    'laravel/pail' => [
        'providers' => [
            0 => 'Laravel\\Pail\\PailServiceProvider',
        ],
    ],
    'laravel/pulse' => [
        'aliases' => [
            'Pulse' => 'Laravel\\Pulse\\Facades\\Pulse',
        ],
        'providers' => [
            0 => 'Laravel\\Pulse\\PulseServiceProvider',
        ],
    ],
    'laravel/reverb' => [
        'providers' => [
            0 => 'Laravel\\Reverb\\ApplicationManagerServiceProvider',
            1 => 'Laravel\\Reverb\\ReverbServiceProvider',
        ],
    ],
    'laravel/roster' => [
        'providers' => [
            0 => 'Laravel\\Roster\\RosterServiceProvider',
        ],
    ],
    'laravel/sail' => [
        'providers' => [
            0 => 'Laravel\\Sail\\SailServiceProvider',
        ],
    ],
    'laravel/sanctum' => [
        'providers' => [
            0 => 'Laravel\\Sanctum\\SanctumServiceProvider',
        ],
    ],
    'laravel/scout' => [
        'providers' => [
            0 => 'Laravel\\Scout\\ScoutServiceProvider',
        ],
    ],
    'laravel/sentinel' => [
        'providers' => [
            0 => 'Laravel\\Sentinel\\SentinelServiceProvider',
        ],
    ],
    'laravel/telescope' => [
        'providers' => [
            0 => 'Laravel\\Telescope\\TelescopeServiceProvider',
        ],
    ],
    'laravel/tinker' => [
        'providers' => [
            0 => 'Laravel\\Tinker\\TinkerServiceProvider',
        ],
    ],
    'laravel/ui' => [
        'providers' => [
            0 => 'Laravel\\Ui\\UiServiceProvider',
        ],
    ],
    'livewire/livewire' => [
        'aliases' => [
            'Livewire' => 'Livewire\\Livewire',
        ],
        'providers' => [
            0 => 'Livewire\\LivewireServiceProvider',
        ],
    ],
    'maatwebsite/excel' => [
        'aliases' => [
            'Excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
        ],
        'providers' => [
            0 => 'Maatwebsite\\Excel\\ExcelServiceProvider',
        ],
    ],
    'nesbot/carbon' => [
        'providers' => [
            0 => 'Carbon\\Laravel\\ServiceProvider',
        ],
    ],
    'nunomaduro/collision' => [
        'providers' => [
            0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
        ],
    ],
    'nunomaduro/termwind' => [
        'providers' => [
            0 => 'Termwind\\Laravel\\TermwindServiceProvider',
        ],
    ],
    'nwidart/laravel-modules' => [
        'aliases' => [
            'Module' => 'Nwidart\\Modules\\Facades\\Module',
        ],
        'providers' => [
            0 => 'Nwidart\\Modules\\LaravelModulesServiceProvider',
        ],
    ],
    'sentry/sentry-laravel' => [
        'aliases' => [
            'Sentry' => 'Sentry\\Laravel\\Facade',
        ],
        'providers' => [
            0 => 'Sentry\\Laravel\\ServiceProvider',
            1 => 'Sentry\\Laravel\\Tracing\\ServiceProvider',
        ],
    ],
    'spatie/laravel-activitylog' => [
        'providers' => [
            0 => 'Spatie\\Activitylog\\ActivitylogServiceProvider',
        ],
    ],
    'spatie/laravel-backup' => [
        'providers' => [
            0 => 'Spatie\\Backup\\BackupServiceProvider',
        ],
    ],
    'spatie/laravel-health' => [
        'aliases' => [
            'Health' => 'Spatie\\Health\\Facades\\Health',
        ],
        'providers' => [
            0 => 'Spatie\\Health\\HealthServiceProvider',
        ],
    ],
    'spatie/laravel-ignition' => [
        'aliases' => [
            'Flare' => 'Spatie\\LaravelIgnition\\Facades\\Flare',
        ],
        'providers' => [
            0 => 'Spatie\\LaravelIgnition\\IgnitionServiceProvider',
        ],
    ],
    'spatie/laravel-medialibrary' => [
        'providers' => [
            0 => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider',
        ],
    ],
    'spatie/laravel-permission' => [
        'providers' => [
            0 => 'Spatie\\Permission\\PermissionServiceProvider',
        ],
    ],
    'spatie/laravel-signal-aware-command' => [
        'aliases' => [
            'Signal' => 'Spatie\\SignalAwareCommand\\Facades\\Signal',
        ],
        'providers' => [
            0 => 'Spatie\\SignalAwareCommand\\SignalAwareCommandServiceProvider',
        ],
    ],
    'torann/geoip' => [
        'aliases' => [
            'GeoIP' => 'Torann\\GeoIP\\Facades\\GeoIP',
        ],
        'providers' => [
            0 => 'Torann\\GeoIP\\GeoIPServiceProvider',
        ],
    ],
    'tymon/jwt-auth' => [
        'aliases' => [
            'JWTAuth' => 'Tymon\\JWTAuth\\Facades\\JWTAuth',
            'JWTFactory' => 'Tymon\\JWTAuth\\Facades\\JWTFactory',
        ],
        'providers' => [
            0 => 'Tymon\\JWTAuth\\Providers\\LaravelServiceProvider',
        ],
    ],
    'webklex/laravel-imap' => [
        'aliases' => [
            'Client' => 'Webklex\\IMAP\\Facades\\Client',
        ],
        'providers' => [
            0 => 'Webklex\\IMAP\\Providers\\LaravelServiceProvider',
        ],
    ],
];
