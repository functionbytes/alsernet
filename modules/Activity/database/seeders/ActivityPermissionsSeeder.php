<?php

namespace Modules\Activity\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ActivityPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'activity.logs.view' => 'Ver registro de actividad',
            'activity.logs.export' => 'Exportar registro de actividad',
            'activity.logs.delete' => 'Eliminar registro de actividad',
            'activity.audit.view' => 'Ver auditoría',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
