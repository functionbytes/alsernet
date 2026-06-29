<?php

namespace App\Providers;

use App\Boost\KimiCode;
use App\Features\EcommerceFeatures;
use App\Http\Api\V1\Manifest\MobileModuleRegistry;
use App\Services\DeepLTranslationService;
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
use Laravel\Boost\Boost;
use Laravel\Horizon\Horizon;
use Laravel\Pennant\Feature;
use Modules\Page\Services\PageService;
use Modules\System\Services\GlobalSearchRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GlobalSearchService::class);
        $this->app->singleton(DeepLTranslationService::class);
        $this->app->singleton(MobileModuleRegistry::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if ($this->app->environment('testing')) {
            $this->app['events']->listen(MigrationsStarted::class, function () {
                // MySQL/MariaDB — sqlite no soporta FOREIGN_KEY_CHECKS
                if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                    DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
                }
            });
        }

        $this->configureApplicationDefaults();
        $this->configureHorizonGate();
        $this->shareGlobalViewData();
        $this->app->make(GlobalSearchRegistrar::class)->register();
        $this->configureRateLimiters();

        Boost::registerCodeEnvironment('.kimi', KimiCode::class);

        $this->defineFeatureFlags();
    }

    private function defineFeatureFlags(): void
    {
        Feature::define(EcommerceFeatures::AI_DESCRIPTIONS, fn () => config('features.ai_descriptions', true));
        Feature::define(EcommerceFeatures::ADVANCED_RECOMMENDATIONS, fn () => config('features.advanced_recommendations', true));
        Feature::define(EcommerceFeatures::CHATBOT_LLM, fn () => config('features.chatbot_llm', false));
        Feature::define(EcommerceFeatures::SUBSCRIPTIONS, fn () => config('features.subscriptions', true));
        Feature::define(EcommerceFeatures::BUNDLES, fn () => config('features.bundles', true));
        Feature::define(EcommerceFeatures::GIFT_CARDS, fn () => config('features.gift_cards', true));
        Feature::define(EcommerceFeatures::SAVED_SEARCHES, fn () => config('features.saved_searches', true));
        Feature::define(EcommerceFeatures::EXIT_INTENT, fn () => config('features.exit_intent', true));
        Feature::define(EcommerceFeatures::VISUAL_SEARCH, fn () => false);
        Feature::define(EcommerceFeatures::MULTI_WAREHOUSE, fn () => false);
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
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by(($request->input('email') ?? '').'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('global-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Helpdesk-specific rate limiters
        RateLimiter::for('helpdesk-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('helpdesk-webhook-inbound', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        RateLimiter::for('helpdesk-customer-portal', function (Request $request) {
            return Limit::perMinute(30)->by(session('portal_customer_id') ?: $request->ip());
        });

        RateLimiter::for('helpdesk-feedback', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('helpdesk-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('helpdesk-export', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('helpdesk-social-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('wompi-webhook', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Mobile API rate limiters
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(5)->by(($request->input('email') ?? '').'|'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('auth-forgot', function (Request $request) {
            return Limit::perHour(3)->by(($request->input('email') ?? '').'|'.$request->ip());
        });

        RateLimiter::for('api-mobile', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-mobile-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
