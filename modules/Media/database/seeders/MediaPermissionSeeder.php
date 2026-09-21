<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MediaPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionNames = [
            'media.view' => 'Ver medios',
            'media.create' => 'Crear medios',
            'media.update' => 'Actualizar medios',
            'media.delete' => 'Eliminar medios',
            'media.manage' => 'Gestionar medios completamente',
            'media.force-delete' => 'Eliminar medios permanentemente',
            'media.settings.view' => 'Ver configuración de medios',
            'media.settings.update' => 'Actualizar configuración de medios',
        ];

        $permissions = collect($permissionNames)->map(fn (string $description, string $name) => Permission::updateOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            ['description' => $description],
        ));

        $adminRole = Role::where('guard_name', 'web')
            ->whereIn('name', ['admin', 'super-admin', 'super-settings'])
            ->first();

        $adminRole?->givePermissionTo($permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
