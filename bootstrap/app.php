<?php

use App\Jobs\Documents\CheckSlaBreachesJob;
use App\Jobs\Documents\SendDocumentReminderJob;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\ViewServiceProvider;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Auth\Http\Middleware\Authenticate;
use Modules\Auth\Http\Middleware\CheckSession;
use Modules\Auth\Http\Middleware\RedirectIfAuthenticated;
use Modules\Core\Http\Middleware\AuditAccessMiddleware;
use Modules\Core\Http\Middleware\CheckSettings;
use Modules\Core\Http\Middleware\EncryptCookies;
use Modules\Core\Http\Middleware\EnsureModuleIsActive;
use Modules\Core\Http\Middleware\HandleCors;
use Modules\Core\Http\Middleware\TrimStrings;
use Modules\Core\Http\Middleware\TrustProxies;
use Modules\Core\Http\Middleware\ValidateSignature;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Document\Http\Middleware\DocumentPermissionMiddleware;
use Modules\Helpdesk\Http\Middleware\EnsureIntegrationEnabled;
use Modules\System\Http\Middleware\PreventRequestsDuringMaintenance;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

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
    ->withSchedule(function (Schedule $schedule) {
        // SLA monitoring commands for Tickets system
        $schedule->command('tickets:check-sla-breaches')->everyFiveMinutes();
        $schedule->command('tickets:sla-warnings')->everyFifteenMinutes();

        // Document reminders - run daily at 09:00
        $schedule->job(SendDocumentReminderJob::class)->daily()->at('09:00');

        // Check SLA breaches for documents - run every hour
        $schedule->job(CheckSlaBreachesJob::class)->hourly();

        // Cleanup commands
        $schedule->command('notifications:clean')->daily();
    })
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware globales
        $middleware->append([
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        // Middleware de grupos (Web y API)
        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            EnsureModuleIsActive::class,
        ]);

        $middleware->group('api', [
            EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'precognitive' => HandlePrecognitiveRequests::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
            'session' => CheckSession::class,

            // Spatie Permission middlewares
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

            // Document permission middleware
            'document.permission' => DocumentPermissionMiddleware::class,

            // Toggle de "Settings > Integraciones" para módulos satélite de Helpdesk
            'integration.enabled' => EnsureIntegrationEnabled::class,

            // Settings section access
            'settings' => CheckSettings::class,

            // Generic GDPR access-audit logging for sensitive read/write endpoints
            'audit.access' => AuditAccessMiddleware::class,

        ]);
    })->withProviders([
        CacheServiceProvider::class, // NECESARIO para Cache::get(), Cache::put()
        DatabaseServiceProvider::class, // NECESARIO para DB::schema(), consultas Eloquent
        FilesystemServiceProvider::class, // NECESARIO para Storage::disk()
        ViewServiceProvider::class, // NECESARIO para Blade y Views
        PaginationServiceProvider::class, // NECESARIO si usas paginación en Eloquent
        TranslationServiceProvider::class, // NECESARIO si usas trans() o __('')
        ValidationServiceProvider::class, // NECESARIO para Validator::make()
        SessionServiceProvider::class, // NECESARIO si usas sesiones con auth
        HashServiceProvider::class, // NECESARIO para Hash::make()
        BusServiceProvider::class, // NECESARIO si usas Jobs y Queue
        QueueServiceProvider::class, // NECESARIO si usas Queue::push()
        PasswordResetServiceProvider::class, // NECESARIO si usas restablecimiento de contraseñas
        NotificationServiceProvider::class, // NECESARIO para Notificaciones con Mail/SMS
        AppServiceProvider::class, // Registra configuraciones personalizadas de tu app
        RouteServiceProvider::class, // Configura rutas y middlewares
        FoundationServiceProvider::class, // NECESARIO para MaintenanceMode
        EncryptionServiceProvider::class, // Agregado para corregir "encrypter"
        CookieServiceProvider::class, // NECESARIO para Cookie::queue()
        AuthServiceProvider::class, // NECESARIO para Auth::attempt(), Auth::user()
        RedisServiceProvider::class, // Agregado para corregir "redis"
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
