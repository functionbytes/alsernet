<?php

namespace Modules\Pulse\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PulsePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'pulse.dashboard.view' => 'Ver panel de rendimiento (Pulse)',
            'pulse.settings.view' => 'Ver configuración de Pulse',
            'pulse.settings.update' => 'Actualizar configuración de Pulse',
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
