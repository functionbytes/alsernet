<?php

namespace Modules\HelpdeskEmailActivity\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskEmailActivityPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'helpdeskemailactivity.view' => 'Ver actividad de correo',
            'helpdeskemailactivity.manage' => 'Gestionar actividad de correo',
            'helpdeskemailactivity.settings.view' => 'Ver configuración de actividad de correo',
            'helpdeskemailactivity.settings.update' => 'Actualizar configuración de actividad de correo',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
