<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => env('AUTH_PWD_MIN_LENGTH', 10),
        'require_uppercase' => env('AUTH_PWD_UPPERCASE', true),
        'require_lowercase' => env('AUTH_PWD_LOWERCASE', true),
        'require_numbers' => env('AUTH_PWD_NUMBERS', true),
        'require_symbols' => env('AUTH_PWD_SYMBOLS', true),
        'reject_compromised' => env('AUTH_PWD_REJECT_COMPROMISED', true),

        // Password history: block reuse of last N passwords
        'history_count' => env('AUTH_PWD_HISTORY', 5),

        // Expiry in days (0 disables expiry)
        'expires_in_days' => env('AUTH_PWD_EXPIRES_DAYS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout (persistent, cross-device)
    |--------------------------------------------------------------------------
    | After N consecutive failed logins the account is locked until the
    | configured duration passes, independent of IP/rate limits.
    */
    'lockout' => [
        'enabled' => env('AUTH_LOCKOUT_ENABLED', true),
        'max_attempts' => env('AUTH_LOCKOUT_MAX_ATTEMPTS', 10),
        'duration_minutes' => env('AUTH_LOCKOUT_DURATION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | 2FA obligatorio por rol (29-sep-2026)
    |--------------------------------------------------------------------------
    | Middleware RequireTwoFactorForPrivilegedRoles (grupo web). Desactivado
    | por defecto para no bloquear a nadie: actívalo con AUTH_REQUIRE_2FA=true
    | cuando los usuarios de estos roles sepan que deben activar 2FA en
    | Perfil > Doble factor. Roles separados por comas en AUTH_REQUIRE_2FA_ROLES.
    */
    'two_factor_enforcement' => [
        'enabled' => (bool) env('AUTH_REQUIRE_2FA', false),
        'roles' => array_values(array_filter(array_map('trim', explode(',', (string) env('AUTH_REQUIRE_2FA_ROLES', 'super-admin,super-settings,helpdesk-admin'))))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Magic Link Login
    |--------------------------------------------------------------------------
    */
    'magic_link' => [
        'enabled' => env('AUTH_MAGIC_LINK_ENABLED', true),
        'expires_in_minutes' => env('AUTH_MAGIC_LINK_TTL', 15),
        'rate_limit_per_email' => env('AUTH_MAGIC_LINK_RATE', 3),
        'rate_limit_window_minutes' => env('AUTH_MAGIC_LINK_WINDOW', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Redis backed)
    |--------------------------------------------------------------------------
    | Combined user_id + IP rate limiters. Overrides default IP-only limits.
    */
    'rate_limits' => [
        'login' => [
            'max_attempts' => env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
            'decay_seconds' => env('AUTH_LOGIN_DECAY', 60),
        ],
        'two_factor' => [
            'max_attempts' => env('AUTH_2FA_MAX_ATTEMPTS', 5),
            'decay_seconds' => env('AUTH_2FA_DECAY', 300),
        ],
        'password_reset' => [
            'max_attempts' => env('AUTH_PWD_RESET_MAX_ATTEMPTS', 3),
            'decay_seconds' => env('AUTH_PWD_RESET_DECAY', 900),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Tracking
    |--------------------------------------------------------------------------
    */
    'devices' => [
        'enabled' => env('AUTH_DEVICE_TRACKING', true),
        'alert_new_device' => env('AUTH_ALERT_NEW_DEVICE', true),
        'trust_duration_days' => env('AUTH_DEVICE_TRUST_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonation
    |--------------------------------------------------------------------------
    */
    'impersonation' => [
        'enabled' => env('AUTH_IMPERSONATION', true),
        'max_duration_minutes' => env('AUTH_IMPERSONATION_MAX_MINUTES', 60),
        'required_permission' => 'auth.impersonate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Screen
    |--------------------------------------------------------------------------
    | Locks session after inactivity; re-auth with password without logout.
    */
    'lock_screen' => [
        'enabled' => env('AUTH_LOCK_SCREEN', true),
        'inactivity_minutes' => env('AUTH_LOCK_INACTIVITY', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Token
    |--------------------------------------------------------------------------
    */
    'reset_token' => [
        'expiry_hours' => env('AUTH_RESET_TOKEN_HOURS', 48),
        'daily_tries' => env('AUTH_RESET_DAILY_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtro por IP del login y del panel (29-sep-2026)
    |--------------------------------------------------------------------------
    | Middleware RestrictStaffAccessByIp (grupos web y api, solo en las rutas
    | del personal listadas abajo). Modos: off | monitor | enforce.
    | - El modo efectivo se guarda en settings (auth.staff_ip_filter.mode);
    |   este 'mode' es solo el valor por defecto si no hay ajuste.
    | - La lista de IPs/CIDR se guarda en settings (auth.staff_ip_filter.allowlist)
    |   y se edita en Panel > Ajustes > Auth > Redes permitidas (solo super-admin).
    |   No se ponen aquí las IPs de la empresa (este fichero va al repositorio).
    | - Emergencia: AUTH_STAFF_IP_FILTER_FORCE_OFF=true en .env, o
    |   `php artisan auth:ip-filter off`.
    */
    'staff_ip_filter' => [
        'mode' => env('AUTH_STAFF_IP_FILTER_MODE', 'monitor'),
        'force_off' => (bool) env('AUTH_STAFF_IP_FILTER_FORCE_OFF', false),

        // Siempre permitidas, fuera de la lista editable.
        'always_allowed' => ['127.0.0.1', '::1'],

        // Rutas del personal que se filtran (patrones de Request::is()).
        // Todo lo demás (portal, widget, webhooks, /api/documents, /api/erp,
        // /app, /up, assets…) queda fuera del filtro.
        'web_paths' => [
            'login', 'logout', 'lock', 'lock/*',
            'forgot-password', 'reset-password', 'reset-password/*',
            'magic-link', 'magic-link/*',
            'two-factor/*',
            'panel', 'panel/*',
            'impersonate/*',
            'broadcasting/auth',
            'settings/health', 'settings/health/*',
        ],
        'api_paths' => ['api/auth', 'api/auth/*'],

        // Además: cualquier otra ruta con sesión web + `auth` (guard web), p. ej.
        // los AJAX del panel bajo /api/documents/* o /api/notifications/*.
        'web_authenticated_routes' => true,

        // Nunca se filtran, aunque coincidan con lo anterior (clientes, tienda,
        // proveedores, ERP con su propio filtro, Reverb, healthcheck).
        'exclude_paths' => [
            'portal', 'portal/*', 'hd', 'hd/*', 'widget', 'widget/*',
            'build-helpdesklivechat', 'build-helpdesklivechat/*',
            'webhooks/*', 'api/*/webhooks/*', 'api/webhooks/*',
            'api/erp', 'api/erp/*', 'app', 'app/*', 'up',
        ],

        // Flujo de login que, en enforce, se deja abrir desde fuera SOLO si hay
        // alguna excepción remota vigente (el acceso al panel se decide después
        // con el usuario ya identificado y su 2FA verificado).
        'login_flow_paths' => ['login', 'logout', 'two-factor/challenge'],

        // Sesiones de acceso remoto (excepción por usuario): duración máxima.
        'remote_session_hours' => (int) env('AUTH_STAFF_REMOTE_SESSION_HOURS', 8),

        // Un registro por IP+usuario+ruta cada N minutos (evita inundar la auditoría).
        'log_dedupe_minutes' => 10,
    ],
];
