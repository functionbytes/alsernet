<?php

namespace Modules\HelpdeskBirthday\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskBirthdayPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'helpdeskbirthday.view' => 'Ver campaña de cumpleaños',
            'helpdeskbirthday.manage' => 'Gestionar campaña de cumpleaños',
            'helpdeskbirthday.settings.view' => 'Ver configuración de cumpleaños',
            'helpdeskbirthday.settings.update' => 'Actualizar configuración de cumpleaños',
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
