<?php

use App\Http\Middleware\ApiVersionHeader;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Mail\CriticalErrorMail;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Auth\Http\Middleware\Authenticate;
use Modules\Auth\Http\Middleware\CheckSession;
use Modules\Auth\Http\Middleware\RedirectIfAuthenticated;
use Modules\Auth\Http\Middleware\TwoFactorChallenge;
use Modules\Core\Http\Middleware\EncryptCookies;
use Modules\Core\Http\Middleware\EnsureModuleIsActive;
use Modules\Core\Http\Middleware\HandleCors;
use Modules\Core\Http\Middleware\SecurityHeaders;
use Modules\Core\Http\Middleware\TrimStrings;
use Modules\Core\Http\Middleware\TrustProxies;
use Modules\Core\Http\Middleware\ValidateSignature;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Page\Http\Middleware\PageCacheMiddleware;
use Modules\Page\Services\ErrorPageService;
use Modules\System\Http\Middleware\PreventRequestsDuringMaintenance;
use Modules\Template\Http\Middleware\RegisterTemplateViewPath;
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
            TrustProxies::class,
            HandleCors::class,
            SecurityHeaders::class,
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
            RegisterTemplateViewPath::class,
            PageCacheMiddleware::class,
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

            // Module status middleware
            'module' => EnsureModuleEnabled::class,

            // Admin roles group - all internal staff roles
            'settings' => RoleMiddleware::class.':super-settings|administrative|manager|callcenter|license|accounting|warehouse|shop|documentation|return',

            // 2FA challenge middleware
            'two-factor' => TwoFactorChallenge::class,

            // API version header
            'api.version' => ApiVersionHeader::class,
        ]);
    })->withProviders([
        AppServiceProvider::class,
        RouteServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            $statusCode = match (true) {
                method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                $e instanceof ValidationException => $e->status,
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
                default => 500,
            };

            // Report to Sentry for server errors (Sentry filters internally by config)
            if (app()->bound('sentry') && $statusCode >= 500) {
                \Sentry\captureException($e);
            }

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

            try {
                $alreadyNotified = Cache::get('error_notification_throttle');

                if ($alreadyNotified) {
                    return;
                }

                Cache::put('error_notification_throttle', true, 300);
            } catch (Throwable) {
                return;
            }

            Mail::to($adminEmail)->queue(new CriticalErrorMail(
                exception: $e,
                url: $request->fullUrl(),
                timestamp: now()->toDateTimeString(),
            ));
        });

        $exceptions->render(function (Throwable $e, $request): ?JsonResponse {
            $statusCode = match (true) {
                method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                $e instanceof ValidationException => $e->status,
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
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

        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return redirect()->guest(route('auth.login'));
            }

            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            if ($statusCode < 400) {
                return null;
            }

            try {
                return app(ErrorPageService::class)->render($statusCode);
            } catch (Throwable) {
                return null;
            }
        });
    })->create();
