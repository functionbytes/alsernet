<?php

namespace Modules\Template\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TemplatePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Template permissions
            ['name' => 'template.view', 'description' => 'Ver plantillas', 'guard_name' => 'web'],
            ['name' => 'template.create', 'description' => 'Crear plantillas', 'guard_name' => 'web'],
            ['name' => 'template.update', 'description' => 'Actualizar plantillas', 'guard_name' => 'web'],
            ['name' => 'template.activate', 'description' => 'Activar plantilla', 'guard_name' => 'web'],
            ['name' => 'template.delete', 'description' => 'Eliminar plantillas', 'guard_name' => 'web'],
            ['name' => 'template.manage', 'description' => 'Gestionar completamente plantillas', 'guard_name' => 'web'],

            // Menu permissions
            ['name' => 'menu.view', 'description' => 'Ver menús', 'guard_name' => 'web'],
            ['name' => 'menu.create', 'description' => 'Crear menús', 'guard_name' => 'web'],
            ['name' => 'menu.update', 'description' => 'Actualizar menús', 'guard_name' => 'web'],
            ['name' => 'menu.delete', 'description' => 'Eliminar menús', 'guard_name' => 'web'],
            ['name' => 'menu.manage', 'description' => 'Gestionar completamente menús', 'guard_name' => 'web'],

            // Shortcode permissions
            ['name' => 'shortcode.view', 'description' => 'Ver shortcodes', 'guard_name' => 'web'],
            ['name' => 'shortcode.create', 'description' => 'Crear shortcodes', 'guard_name' => 'web'],
            ['name' => 'shortcode.update', 'description' => 'Actualizar shortcodes', 'guard_name' => 'web'],
            ['name' => 'shortcode.delete', 'description' => 'Eliminar shortcodes', 'guard_name' => 'web'],
            ['name' => 'shortcode.manage', 'description' => 'Gestionar completamente shortcodes', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['description' => $permission['description']]
            );

            $this->command->info("Permiso creado: {$permission['name']}");
        }

        $this->command->info('');
        $this->command->info('Permisos de Template, Menu y Shortcode creados exitosamente.');
    }
}
