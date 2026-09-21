<?php

namespace Modules\Health\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HealthPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'health.view' => 'Ver estado de salud del sistema',
            'health.history.view' => 'Ver historial de salud del sistema',
            'health.history.delete' => 'Eliminar historial de salud del sistema',
            'health.alerts.view' => 'Ver alertas de salud del sistema',
            'health.alerts.create' => 'Crear alertas de salud del sistema',
            'health.alerts.update' => 'Actualizar alertas de salud del sistema',
            'health.alerts.delete' => 'Eliminar alertas de salud del sistema',
            'health.supervisor.generate' => 'Generar configuración de supervisor',
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
