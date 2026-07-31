<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // SLA monitoring commands for Tickets system
        $schedule->command('tickets:check-sla-breaches')->everyFiveMinutes();
        $schedule->command('tickets:sla-warnings')->everyFifteenMinutes();

        // Document reminders - run daily at 09:00
        $schedule->job(\App\Jobs\Documents\SendDocumentReminderJob::class)->daily()->at('09:00');

        // Check SLA breaches for documents - run every hour
        $schedule->job(\App\Jobs\Documents\CheckSlaBreachesJob::class)->hourly();

        // Cleanup commands
        $schedule->command('notifications:clean')->daily();
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware globales
        $middleware->append([
            \Modules\Core\Http\Middleware\TrustProxies::class,
            \Modules\Core\Http\Middleware\HandleCors::class,
            \Modules\System\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Modules\Core\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // Middleware de grupos (Web y API)
        $middleware->group('web', [
            \Modules\Core\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Modules\Core\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Modules\Core\Http\Middleware\EnsureModuleIsActive::class,
        ]);

        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \Modules\Auth\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \Modules\Auth\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \Modules\Core\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'session' => \Modules\Auth\Http\Middleware\CheckSession::class,

            // Spatie Permission middlewares
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // Document permission middleware
            'document.permission' => \Modules\Document\Http\Middleware\DocumentPermissionMiddleware::class,

            // Toggle de "Settings > Integraciones" para módulos satélite de Helpdesk
            'integration.enabled' => \Modules\Helpdesk\Http\Middleware\EnsureIntegrationEnabled::class,

            // Settings section access
            'settings' => \Modules\Core\Http\Middleware\CheckSettings::class,

        ]);
    })->withProviders([
        Illuminate\Cache\CacheServiceProvider::class, // NECESARIO para Cache::get(), Cache::put()
        Illuminate\Database\DatabaseServiceProvider::class, // NECESARIO para DB::schema(), consultas Eloquent
        Illuminate\Filesystem\FilesystemServiceProvider::class, // NECESARIO para Storage::disk()
        Illuminate\View\ViewServiceProvider::class, // NECESARIO para Blade y Views
        Illuminate\Pagination\PaginationServiceProvider::class, // NECESARIO si usas paginación en Eloquent
        Illuminate\Translation\TranslationServiceProvider::class, // NECESARIO si usas trans() o __('')
        Illuminate\Validation\ValidationServiceProvider::class, // NECESARIO para Validator::make()
        Illuminate\Session\SessionServiceProvider::class, // NECESARIO si usas sesiones con auth
        Illuminate\Hashing\HashServiceProvider::class, // NECESARIO para Hash::make()
        Illuminate\Bus\BusServiceProvider::class, // NECESARIO si usas Jobs y Queue
        Illuminate\Queue\QueueServiceProvider::class, // NECESARIO si usas Queue::push()
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class, // NECESARIO si usas restablecimiento de contraseñas
        Illuminate\Notifications\NotificationServiceProvider::class, // NECESARIO para Notificaciones con Mail/SMS
        App\Providers\AppServiceProvider::class, // Registra configuraciones personalizadas de tu app
        App\Providers\RouteServiceProvider::class, // Configura rutas y middlewares
        Illuminate\Foundation\Providers\FoundationServiceProvider::class, // NECESARIO para MaintenanceMode
        Illuminate\Encryption\EncryptionServiceProvider::class, // Agregado para corregir "encrypter"
        Illuminate\Cookie\CookieServiceProvider::class, // NECESARIO para Cookie::queue()
        Illuminate\Auth\AuthServiceProvider::class, // NECESARIO para Auth::attempt(), Auth::user()
        Illuminate\Redis\RedisServiceProvider::class, // Agregado para corregir "redis"
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
