<?php

namespace Modules\HelpdeskContacts\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskContactsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'modules.view.contacts' => 'Ver módulo de Contactos',
            'contacts.view' => 'Ver contactos',
            'contacts.create' => 'Crear/importar contactos',
            'contacts.update' => 'Actualizar contactos',
            'contacts.commerce' => 'Ver datos comerciales del contacto (pedidos, carrito)',
            'contacts.insights' => 'Ver estadísticas del contacto',
            'contacts.merge' => 'Fusionar contactos duplicados',
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
