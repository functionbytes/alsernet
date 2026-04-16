<?php

namespace App\Providers;

use App\Services\DeepLTranslationService;
use App\Services\GlobalSearchRegistrar;
use App\Services\GlobalSearchService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Modules\Page\Services\PageService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GlobalSearchService::class);
        $this->app->singleton(DeepLTranslationService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if ($this->app->environment('testing')) {
            $this->app['events']->listen(MigrationsStarted::class, function () {
                DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
            });
        }

        $this->configureApplicationDefaults();
        $this->configureHorizonGate();
        $this->shareGlobalViewData();
        $this->app->make(GlobalSearchRegistrar::class)->register();
        $this->configureRateLimiters();
    }

    private function configureHorizonGate(): void
    {
        Horizon::auth(function (Request $request) {
            return auth()->check() && auth()->user()->hasRole(['super-settings', 'settings']);
        });
    }

    private function shareGlobalViewData(): void
    {
        View::composer('*', function ($view) {
            if (! $view->offsetExists('supportedLocales')) {
                $view->with('supportedLocales', PageService::getSupportedLocales());
            }
        });
    }

    private function configureApplicationDefaults(): void
    {
        if (request()->isSecure() || (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('global-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
