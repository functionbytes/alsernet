<?php

namespace Modules\Backup\Providers;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Routes are defined in routes/web.php with middleware and prefix
        // This provider exists for consistency with module structure
        require module_path('Backup', 'routes/web.php');
    }
}
