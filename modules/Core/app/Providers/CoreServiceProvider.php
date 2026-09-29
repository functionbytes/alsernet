<?php

namespace Modules\Core\Providers;

use App\Support\Security\SecurityCounters;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\Commands\OptimizeProductionCommand;
use Modules\Core\Http\Controllers\CspReportController;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\Settings\SecurityConfigController;
use Modules\Core\Services\SecurityConfigService;
use Modules\Theme\Services\NavService;

class CoreServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Core';

    protected string $moduleNameLower = 'core';

    public function register(): void
    {
        // Merge module config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/languages.php',
            'languages'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/localization.php',
            'localization'
        );

        // 29-sep-2026 (H4): el servidor MCP se decide al cargar routes/ai.php,
        // antes de que arranque ningún provider; su ajuste se aplica aquí.
        if (! $this->skipSecurityConfigOverrides()) {
            $this->app->booting(fn () => SecurityConfigService::applyEarly());
        }
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();

        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);

        // Register routes after all providers have booted
        $this->booted(function () {
            $this->registerRoutes();
        });

        // Register menus
        $this->registerMenus();

        // 29-sep-2026: contadores para `security:watch` y throttle de /csp-report
        $this->registerSecurityMonitoring();

        // 29-sep-2026 (H4): "Configuración de seguridad" (solo super-admin).
        $this->registerSecurityConfig();

        // Register scheduled tasks
        $this->registerSchedules();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                OptimizeProductionCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        // 29-sep-2026: informes CSP. Fuera del grupo `web` a propósito: sin
        // sesión, cookies ni CSRF (el navegador los envía sin token). Throttle propio.
        Route::post(config('security.csp.report_path', '/csp-report'), CspReportController::class)
            ->middleware('throttle:csp-report')
            ->name('security.csp-report');

        Route::middleware(['web', 'auth'])
            ->prefix('panel')
            ->name('core.')
            ->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
                Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity'])->name('dashboard.activity');
                Route::get('/dashboard/queue-stats', [DashboardController::class, 'queueStats'])->name('dashboard.queue-stats');
                Route::get('/dashboard/trends', [DashboardController::class, 'trends'])->name('dashboard.trends');
                Route::get('/dashboard/health', [DashboardController::class, 'health'])->name('dashboard.health');
                Route::get('/dashboard/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
                Route::get('/dashboard/distribution', [DashboardController::class, 'distribution'])->name('dashboard.distribution');
                Route::get('/dashboard/latest-reviews', [DashboardController::class, 'latestReviews'])->name('dashboard.latest-reviews');
                Route::get('/dashboard/security-metrics', [DashboardController::class, 'securityMetrics'])->name('dashboard.security-metrics');
                Route::get('/dashboard/login-timeline', [DashboardController::class, 'loginAttemptsTimeline'])->name('dashboard.login-timeline');
                Route::get('/dashboard/top-failed-ips', [DashboardController::class, 'topFailedIps'])->name('dashboard.top-failed-ips');
            });

        // 29-sep-2026 (H4): Configuración de seguridad. Solo super-admin (Gate
        // security.config.manage aquí y en el controlador).
        Route::middleware([
            'web', 'auth',
            \Modules\Auth\Http\Middleware\CheckSessionLock::class,
            \Modules\Auth\Http\Middleware\CheckPasswordExpired::class,
            'can:security.config.manage',
        ])
            ->prefix('panel/settings/security/config')
            ->name('settings.security.')
            ->group(function () {
                Route::get('/', [SecurityConfigController::class, 'index'])->name('config');
                Route::middleware(['auth.deny-impersonating', 'throttle:30,1'])->group(function () {
                    Route::post('/secret/generate', [SecurityConfigController::class, 'generateSecret'])->name('config.generate-secret');
                    Route::post('/reset/{key}', [SecurityConfigController::class, 'reset'])
                        ->where('key', '[a-z0-9_]+')->name('config.reset');
                    Route::post('/{section}', [SecurityConfigController::class, 'update'])
                        ->whereIn('section', SecurityConfigController::SECTIONS)->name('config.update');
                });
            });
    }

    /**
     * 29-sep-2026: limitador del endpoint de informes CSP y contadores por minuto
     * (denegaciones de la API ERP, logins fallidos) que lee `security:watch`.
     */
    protected function registerSecurityMonitoring(): void
    {
        RateLimiter::for('csp-report', function (Request $request) {
            return Limit::perMinute((int) config('security.csp.report_throttle_per_minute', 60))
                ->by($request->ip());
        });

        Event::listen(MessageLogged::class, [SecurityCounters::class, 'onMessageLogged']);

        Event::listen(Failed::class, fn () => SecurityCounters::increment('failed_logins'));
        Event::listen(Lockout::class, fn () => SecurityCounters::increment('failed_logins'));
    }

    /**
     * 29-sep-2026 (H4): ajustes de seguridad guardados en `settings` que
     * sobrescriben config/.env en runtime (ver SecurityConfigService). Se
     * aplican cuando ya han arrancado todos los providers (los módulos fusionan
     * o releen su config en boot) y al empezar cada job, para que los workers
     * de larga vida vean los cambios sin reiniciarse.
     */
    protected function registerSecurityConfig(): void
    {
        Gate::define('security.config.manage', fn ($user) => $user->hasRole('super-admin'));

        if ($this->skipSecurityConfigOverrides()) {
            return;
        }

        $this->app->booted(fn () => SecurityConfigService::apply());
        Event::listen(JobProcessing::class, fn () => SecurityConfigService::apply());
    }

    /**
     * Al generar la caché de config no se aplican los ajustes: quedarían
     * congelados en bootstrap/cache/config.php y "volver al valor de .env"
     * dejaría de funcionar. Se siguen aplicando en cada petición.
     */
    private function skipSecurityConfigOverrides(): bool
    {
        if (! $this->app->runningInConsole()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? '';

        return in_array($command, ['config:cache', 'optimize', 'system:optimize-production'], true);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
    }

    protected function registerConfig(): void
    {
        // Register config files from module if they exist
        $configPath = module_path($this->moduleName, 'config');
        if (is_dir($configPath)) {
            foreach (glob($configPath.'/*.php') as $configFile) {
                $this->publishes([
                    $configFile => config_path(basename($configFile)),
                ], $this->moduleNameLower.'-config');
            }
        }
    }

    /**
     * Registrar menús del módulo Core
     */
    protected function registerMenus(): void
    {
        // Mini-nav item para Core/Dashboard
        NavService::registerMiniItem('dashboard', [
            'icon' => 'gauge',
            'tooltip' => 'Panel de control',
            'sidebar_id' => 'dashboard',
            'url' => 'core.dashboard',
            'order' => 10,
        ]);

        // Sidebar con los items del módulo
        NavService::registerSidebar('dashboard', [
            'title' => 'Panel de control',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'core.dashboard'],
            ],
        ]);
    }

    /**
     * Register scheduled tasks for Core module
     */
    protected function registerSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // System cleanup - daily
            $schedule->command('system:cleanup')->daily();

            // GeoIP database check - daily
            $schedule->command('geoip:check')->daily();

            // Cache/config/route clearing is handled by the deploy pipeline, not the scheduler.
            // Running these every 30 minutes in production causes race conditions and performance issues.
        });
    }
}
