<?php

namespace Modules\Locales\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LocalesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'locale.view' => 'Ver idiomas',
            'locale.create' => 'Crear idiomas',
            'locale.update' => 'Actualizar idiomas',
            'locale.delete' => 'Eliminar idiomas',
            'locale.set-default' => 'Establecer idioma por defecto',
            'locale.toggle' => 'Activar/desactivar idioma',
            'locale.translate' => 'Traducir contenido',
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
