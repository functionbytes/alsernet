<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Routes are now registered in CoreServiceProvider::registerRoutes()
        // Routes are NOT loaded from routes/web.php - see CoreServiceProvider for implementation
    }
}
