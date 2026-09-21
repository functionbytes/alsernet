<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Role\Helpers\PermissionHelper;
use Spatie\Permission\Models\Permission;

class ModulePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // TODOS los módulos Composer instalados y habilitados (misma fuente
        // que RoleController::showModules), no solo los que tienen entrada
        // propia en el sidebar, para que la pantalla de "módulos visibles
        // por rol" no deje ninguno fuera.
        $modules = PermissionHelper::allModulesForVisibilityToggle();

        foreach ($modules as $moduleId => $moduleName) {
            Permission::firstOrCreate(
                ['name' => "modules.view.{$moduleId}", 'guard_name' => 'web'],
                ['description' => "Ver módulo de {$moduleName}"]
            );
        }

        $this->command->info('Permisos de módulos creados correctamente.');
    }
}
