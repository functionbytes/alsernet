<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/panel/dashboard';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {

            Route::middleware('web')
                ->group(function () {
                    Route::group([], base_path('routes/web.php'));
                });
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('api-strict', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(30)->by($request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('public-pages', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('public-pages-search', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
