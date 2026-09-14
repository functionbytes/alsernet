<?php

namespace Modules\GiftMessage\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GiftMessagePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['giftmessage.view', 'Ver mensajes regalo'],
            ['giftmessage.create', 'Crear/generar mensajes regalo'],
            ['giftmessage.update', 'Actualizar configuracion de mensajes regalo'],
            ['giftmessage.delete', 'Eliminar mensajes regalo'],
            ['giftmessage.manage', 'Gestionar mensajes regalo completamente'],
            ['modules.view.giftmessage', 'Ver modulo Mensaje Regalo en el nav'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        $permissionNames = array_column($permissions, 0);

        $adminRoles = Role::whereIn('name', ['super-admin', 'super-settings'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissionNames);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
