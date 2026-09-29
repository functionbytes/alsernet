<?php

/*
|--------------------------------------------------------------------------
| Seguridad transversal (29-sep-2026)
|--------------------------------------------------------------------------
|
| - headers: cabeceras de seguridad que añade el middleware global
|   Modules\Core\Http\Middleware\SecurityHeaders.
| - csp: Content-Security-Policy (en modo Report-Only por defecto) y su
|   endpoint de informes POST /csp-report (canal de log "csp").
| - watch: umbrales de `php artisan security:watch` (cada 5 min).
| - audit: parámetros de `php artisan security:audit`.
|
| Documentación: docs/seguridad/CABECERAS_Y_VIGILANCIA.md
|
*/

$reverbHost = env('REVERB_HOST', 'webadmin.a-alvarez.com');

return [

    'headers' => [
        'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),

        'x_content_type_options' => 'nosniff',

        'referrer_policy' => 'strict-origin-when-cross-origin',

        // camera/microphone/display-capture: videollamada WebRTC del Helpdesk.
        'permissions_policy' => 'camera=(self), microphone=(self), display-capture=(self), '
            .'geolocation=(), payment=(), usb=(), serial=(), bluetooth=(), hid=(), midi=(), '
            .'magnetometer=(), gyroscope=(), accelerometer=()',

        // Apache (vhost webadmin.a-alvarez.com:443) ya envía "X-Frame-Options:
        // SAMEORIGIN" y HSTS (max-age=63072000) con `Header always set`. Si la app
        // también los enviara, la respuesta llevaría la cabecera duplicada. Poner
        // SECURITY_X_FRAME_OPTIONS=SAMEORIGIN / SECURITY_HSTS=true solo si se
        // sirve la app sin ese Apache.
        'frame_options' => env('SECURITY_X_FRAME_OPTIONS'),
        'hsts' => (bool) env('SECURITY_HSTS', false),
        'hsts_value' => 'max-age=31536000; includeSubDomains',

        // Rutas pensadas para incrustarse en iframe desde la web de la tienda.
        // Reciben un CSP (bloqueante) con solo `frame-ancestors`, que en los
        // navegadores actuales tiene prioridad sobre el X-Frame-Options de Apache.
        // El widget de livechat de www.a-alvarez.com NO usa iframe (inyecta
        // /build-helpdesklivechat/widget.js + /hd/assets/*), así que no depende de esto.
        'embeddable_paths' => [
            'forms/embed/*',
            'hd/widget',
            'hd/widget/*',
            'pages/helpdesk-widget',
            'pages/helpdesk-widget/*',
        ],
        'frame_ancestors' => env(
            'SECURITY_FRAME_ANCESTORS',
            "'self' https://a-alvarez.com https://*.a-alvarez.com"
        ),

        // X-Robots-Tag en TODAS las respuestas de la app (panel, API, portal,
        // widget, formularios públicos, redirecciones, descargas y errores):
        // webadmin no debe indexarse. null/'' lo desactiva.
        'robots_tag' => env('SECURITY_X_ROBOTS_TAG', 'noindex, nofollow, noarchive, nosnippet'),
    ],

    'csp' => [
        'enabled' => (bool) env('SECURITY_CSP_ENABLED', true),

        // true  => Content-Security-Policy-Report-Only (no bloquea, solo informa)
        // false => Content-Security-Policy (bloqueante). Ver la guía antes de cambiarlo.
        'report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', true),

        'report_path' => '/csp-report',

        // Informes aceptados por IP y minuto en /csp-report (el resto → 429).
        'report_throttle_per_minute' => 60,

        // Tamaño máximo del cuerpo de un informe (bytes).
        'report_max_bytes' => 16384,

        // Inventario del 29-sep-2026 (vistas Blade, JS de módulos y build de Vite).
        // 'unsafe-inline'/'unsafe-eval' son necesarios hoy: cientos de <script>
        // inline, onclick="..." y plugins jQuery; quitarlos exige nonces (ver guía).
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'", "'unsafe-inline'", "'unsafe-eval'", 'blob:',
                'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
                'https://code.jquery.com', 'https://js.pusher.com',
            ],
            'style-src' => [
                "'self'", "'unsafe-inline'",
                'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
                'https://fonts.googleapis.com', 'https://fonts.bunny.net', 'https://rsms.me',
            ],
            'font-src' => [
                "'self'", 'data:',
                'https://fonts.gstatic.com', 'https://fonts.bunny.net', 'https://rsms.me',
                'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
            ],
            // Imágenes remotas legítimas: adjuntos/avatares de Meta/WhatsApp, emails
            // HTML, favicons de Google, mapas estáticos de OSM, QR de 2FA...
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'media-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'connect-src' => [
                "'self'",
                'wss://'.$reverbHost, 'https://'.$reverbHost,
                'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com',
            ],
            'frame-src' => [
                "'self'", 'blob:', 'data:',
                'https://www.youtube.com', 'https://www.youtube-nocookie.com',
            ],
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ],
    ],

    'watch' => [
        // Directorios públicos donde NO debería haber código ejecutable.
        'upload_dirs' => [
            'storage/app/public',
            'storage/app/media',
            'public',
        ],

        // Extensiones/nombres sospechosos en esos directorios (regex sobre el nombre).
        'suspicious_name_pattern' => '/(\.(php\d*|phtml|pht|phar|phps|cgi|pl|py|sh)(\.|$))|^\.htaccess$|^\.user\.ini$/i',

        // Solo se lee el contenido (busca "<?php" / "<?=") de ficheros hasta este tamaño.
        'content_scan_max_bytes' => 2 * 1024 * 1024,

        // Subdirectorios de public/ con assets compilados: no se lee su contenido
        // (sí se vigilan nombres sospechosos). La línea base cubre el resto.
        'content_scan_skip' => [
            'public/build',
            'public/build-helpdesklivechat',
            'public/vendor',
        ],

        // Código PHP cuyo cambio se detecta entre pasadas (mtime+size, hash si cambió).
        'code_dirs' => ['app', 'modules', 'config', 'routes', 'bootstrap', 'vendor'],
        'code_skip' => ['bootstrap/cache', 'node_modules'],

        // Ventana y umbrales (conteos dentro de la ventana).
        'window_minutes' => 5,
        'thresholds' => [
            // 403 "ERP API: acceso denegado" (IP no permitida).
            'erp_denied_403' => (int) env('SECURITY_WATCH_ERP_403', 10),
            // 401 de la API ERP (token/credenciales incorrectas).
            'erp_denied_401' => (int) env('SECURITY_WATCH_ERP_401', 30),
            // Logins fallidos (failed/lockout/2fa_failed) en total.
            'failed_logins' => (int) env('SECURITY_WATCH_FAILED_LOGINS', 20),
            // Logins fallidos desde una misma IP.
            'failed_logins_per_ip' => (int) env('SECURITY_WATCH_FAILED_LOGINS_IP', 10),
            // Logins fallidos contra una misma cuenta.
            'failed_logins_per_email' => (int) env('SECURITY_WATCH_FAILED_LOGINS_EMAIL', 8),
        ],

        // No repetir la misma alerta de umbral antes de estos minutos.
        'alert_cooldown_minutes' => 30,

        // Máximo de ficheros listados en una alerta.
        'max_files_in_alert' => 40,

        // Rol que recibe las alertas.
        'notify_role' => 'super-admin',

        // Store de caché de los contadores (Redis "limiter": sobrevive a cache:clear).
        'counter_store' => 'limiter',
    ],

    'audit' => [
        'approved_routes_file' => 'docs/seguridad/rutas_publicas_aprobadas.txt',
        'baseline_file' => 'docs/seguridad/auditoria_linea_base.json',
    ],

];
