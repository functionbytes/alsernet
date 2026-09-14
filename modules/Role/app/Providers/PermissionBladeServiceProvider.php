<?php

namespace Modules\Role\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class PermissionBladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Directiva para verificar módulos
        Blade::directive('module', function ($expression) {
            return "<?php if(\Modules\Role\Helpers\PermissionHelper::canAccessModule({$expression})): ?>";
        });

        Blade::directive('endmodule', function () {
            return '<?php endif; ?>';
        });

        // Directiva para verificar múltiples roles
        Blade::directive('hasanyrole', function ($expression) {
            return "<?php if(\Modules\Role\Helpers\PermissionHelper::hasAnyRole({$expression})): ?>";
        });

        Blade::directive('endhasanyrole', function () {
            return '<?php endif; ?>';
        });

        // Directiva para verificar múltiples permisos
        Blade::directive('hasanypermission', function ($expression) {
            return "<?php if(\Modules\Role\Helpers\PermissionHelper::hasAnyPermission({$expression})): ?>";
        });

        Blade::directive('endhasanypermission', function () {
            return '<?php endif; ?>';
        });

        // Directiva para acciones en devoluciones
        Blade::directive('canmanagereturn', function ($expression) {
            [$return, $action] = array_map('trim', explode(',', $expression, 2));

            return "<?php if(\Modules\Role\Helpers\PermissionHelper::canManageReturn({$return}, {$action})): ?>";
        });

        Blade::directive('endcanmanagereturn', function () {
            return '<?php endif; ?>';
        });
    }
}
