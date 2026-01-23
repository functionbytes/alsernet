<?php

namespace Modules\Queue\Providers;

use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerConfigurations();
    }

    protected function registerConfigurations(): void
    {
        $this->publishes([
            __DIR__.'/../../config/queue.php' => config_path('queue.php'),
        ], 'config');
    }
}
