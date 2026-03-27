<?php

use App\Mail\CriticalErrorMail;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        $schedule->command('tickets:check-sla-breaches')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('tickets:sla-warnings')->everyFifteenMinutes()->withoutOverlapping();

        // Alert thresholds check
        $schedule->command('alerts:check')->everyFiveMinutes()->withoutOverlapping();

        // Cleanup commands
        $schedule->command('notifications:cleanup')->daily();
        $schedule->command('activity:prune')->weekly();
        $schedule->command('forms:cleanup-abandoned')->weekly();
        $schedule->command('forms:cleanup-tokens')->daily();
        $schedule->command('forms:prune-versions')->monthly();
        $schedule->command('exports:cleanup --days=1')->daily();
        $schedule->command('translations:missing')->monthly()->emailOutputOnFailure(config('monitoring.alerts.admin_email'));

        // Analytics scheduled reports are managed by AnalyticsServiceProvider
        // via analytics:dispatch-schedules (registered with callAfterResolving).
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
            \Modules\Template\Http\Middleware\RegisterTemplateViewPath::class,
            \Modules\Page\Http\Middleware\PageCacheMiddleware::class,
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

            // Module status middleware
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,

            // Admin roles group - all internal staff roles
            'settings' => \Spatie\Permission\Middleware\RoleMiddleware::class.':super-settings|administrative|manager|callcenter|license|accounting|warehouse|shop|documentation|return',

            // 2FA challenge middleware
            'two-factor' => \Modules\Auth\Http\Middleware\TwoFactorChallenge::class,

            // API version header
            'api.version' => \App\Http\Middleware\ApiVersionHeader::class,
        ]);
    })->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            $statusCode = match (true) {
                method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                $e instanceof \Illuminate\Validation\ValidationException => $e->status,
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                default => 500,
            };

            if ($statusCode < 500) {
                return;
            }

            $request = request();

            Log::error('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $adminEmail = config('mail.admin_address');

            if (! $adminEmail) {
                return;
            }

            $alreadyNotified = Cache::get('error_notification_throttle');

            if ($alreadyNotified) {
                return;
            }

            Cache::put('error_notification_throttle', true, 300);

            Mail::to($adminEmail)->queue(new CriticalErrorMail(
                exception: $e,
                url: $request->fullUrl(),
                timestamp: now()->toDateTimeString(),
            ));
        });

        $exceptions->render(function (Throwable $e, $request): ?JsonResponse {
            $statusCode = match (true) {
                method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                $e instanceof \Illuminate\Validation\ValidationException => $e->status,
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                default => 500,
            };

            if ($statusCode < 500 || ! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'error' => 'Internal server error',
                'code' => 500,
            ], 500);
        });
    })->create();
