<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use Throwable;

class SentryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Conditional registration — only if the Sentry package is installed
    }

    public function boot(): void
    {
        if (! class_exists(SentrySdk::class)) {
            return;
        }

        $dsn = config('services.sentry.dsn');

        if (empty($dsn)) {
            return;
        }

        if (! SentrySdk::getCurrentHub()->getClient()) {
            \Sentry\init([
                'dsn' => $dsn,
                'environment' => config('services.sentry.environment', config('app.env')),
                'traces_sample_rate' => (float) config('services.sentry.traces_sample_rate', 0.1),
                'release' => config('app.version'),
            ]);
        }

        $this->app['events']->listen('eloquent.*', function () {
            if (! auth()->check()) {
                return;
            }

            if (! SentrySdk::getCurrentHub()->getClient()) {
                return;
            }

            \Sentry\configureScope(function (Scope $scope) {
                $user = auth()->user();

                if (! $user) {
                    return;
                }

                $scope->setUser([
                    'id' => (string) $user->id,
                    'email' => $user->email ?? null,
                    'username' => trim(($user->firstname ?? '').' '.($user->lastname ?? '')),
                ]);
            });
        });
    }

    /**
     * Capture an exception manually — safe to call even if Sentry isn't installed.
     */
    public static function captureException(Throwable $e): void
    {
        if (class_exists(SentrySdk::class) && SentrySdk::getCurrentHub()->getClient()) {
            \Sentry\captureException($e);
        }
    }
}
