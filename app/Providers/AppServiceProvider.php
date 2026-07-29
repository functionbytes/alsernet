<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $this->configureApplicationDefaults();
    }

    private function configureApplicationDefaults(): void
    {

        ini_set('memory_limit', '-1');
        ini_set('pcre.backtrack_limit', '1000000000');

        // Force HTTPS if request is secure or header indicates it
        if (request()->isSecure() || (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)) {
            URL::forceScheme('https');
        }
    }
}
