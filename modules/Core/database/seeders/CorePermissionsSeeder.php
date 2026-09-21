<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CorePermissionsSeeder extends Seeder
{
    /**
     * El módulo Core nunca tuvo seeder de permisos propio. Su
     * DashboardController (panel principal: KPIs, salud, seguridad, cola)
     * autoriza con 'settings.view'/'settings.system', que no existían en
     * ningún sitio — 403 permanente para cualquier usuario, incluido
     * super-admin.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'settings.view' => 'Ver el panel principal (KPIs, actividad, salud del sistema)',
            'settings.system' => 'Ver estado de las colas del sistema',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
