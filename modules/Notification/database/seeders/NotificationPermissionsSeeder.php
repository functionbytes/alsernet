<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class NotificationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $this->createPermissions();

        // Assign to roles
        $this->assignToRoles();
    }

    /**
     * Create all notification permissions.
     */
    protected function createPermissions(): void
    {
        $permissions = [
            // Web module permissions
            [
                'name' => 'notification.view',
                'description' => 'Ver notificaciones propias',
            ],
            [
                'name' => 'notification.manage',
                'description' => 'Gestionar todas las notificaciones del sistema',
            ],

            // Settings permissions
            [
                'name' => 'notification.settings.view',
                'description' => 'Ver configuración del módulo de notificaciones',
            ],
            [
                'name' => 'notification.settings.update',
                'description' => 'Actualizar configuración del módulo de notificaciones',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['description' => $permission['description']]
            );

            $this->command->info("Permiso creado: {$permission['name']}");
        }
    }

    /**
     * Assign permissions to existing roles.
     */
    protected function assignToRoles(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-settings', 'guard_name' => 'web']
        );

        $superAdmin->givePermissionTo([
            'notification.view',
            'notification.manage',
            'notification.settings.view',
            'notification.settings.update',
        ]);

        $this->command->info('Permisos asignados al rol: super-settings');

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Permisos de Notification creados exitosamente!');
        $this->command->info('=================================================');
    }
}
