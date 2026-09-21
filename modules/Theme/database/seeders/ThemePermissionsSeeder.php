<?php

namespace Modules\Theme\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ThemePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'theme.view' => 'Ver configuración del tema',
            'theme.update' => 'Actualizar configuración del tema',
            'theme.assets.serve' => 'Servir recursos estáticos del tema',
            'modules.view.theme' => 'Ver módulo de Tema',
            'modules.view.dashboard' => 'Ver módulo de Dashboard',
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
