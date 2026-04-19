<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AttentionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles($permissions);
    }

    /**
     * Create all attention permissions.
     */
    protected function createPermissions(): array
    {
        $permissions = [
            // Basic permissions
            [
                'name' => 'attention.view',
                'description' => 'Ver peticiones asignados o del departamento',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.view-all',
                'description' => 'Ver todos los peticiones sin restricciones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.create',
                'description' => 'Crear nuevos peticiones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.update',
                'description' => 'Actualizar peticiones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.delete',
                'description' => 'Eliminar peticiones',
                'guard_name' => 'web',
            ],

            // Advanced permissions
            [
                'name' => 'attention.manage',
                'description' => 'Gestionar completamente peticiones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.assign',
                'description' => 'Asignar peticiones a usuarios o departamentos',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.change-status',
                'description' => 'Cambiar estado de peticiones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.resolve',
                'description' => 'Resolver peticiones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.close',
                'description' => 'Cerrar peticiones',
                'guard_name' => 'web',
            ],

            // Communication permissions
            [
                'name' => 'attention.send-email',
                'description' => 'Enviar emails relacionados con peticiones',
                'guard_name' => 'web',
            ],

            // Notes permissions
            [
                'name' => 'attention.manage-notes',
                'description' => 'Gestionar notas de peticiones',
                'guard_name' => 'web',
            ],

            // History permissions
            [
                'name' => 'attention.view-history',
                'description' => 'Ver historial completo de peticiones',
                'guard_name' => 'web',
            ],

            // Department permissions
            [
                'name' => 'attention.manage-departments',
                'description' => 'Gestionar departamentos de atención',
                'guard_name' => 'web',
            ],

            // Type permissions
            [
                'name' => 'attention.manage-types',
                'description' => 'Gestionar tipos de peticiones',
                'guard_name' => 'web',
            ],

            // Settings permissions
            [
                'name' => 'attention.manage-settings',
                'description' => 'Gestionar configuración del módulo',
                'guard_name' => 'web',
            ],

            // Reports permissions
            [
                'name' => 'attention.view-reports',
                'description' => 'Ver reportes y estadísticas',
                'guard_name' => 'web',
            ],
        ];

        $createdPermissions = [];

        foreach ($permissions as $permission) {
            $createdPermissions[] = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['description' => $permission['description'] ?? '']
            );

            $this->command->info("Permiso creado: {$permission['name']}");
        }

        return $createdPermissions;
    }

    /**
     * Create roles and assign permissions.
     */
    protected function createRoles(array $permissions): void
    {
        // Super Admin - Has all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-settings', 'guard_name' => 'web']
        );
        $this->command->info('Rol creado: super-settings');

        // Attention Administrator - Full module control
        $attentionAdmin = Role::firstOrCreate(
            ['name' => 'attention-settings', 'guard_name' => 'web']
        );
        $attentionAdmin->givePermissionTo([
            'attention.view-all',
            'attention.create',
            'attention.update',
            'attention.delete',
            'attention.manage',
            'attention.assign',
            'attention.change-status',
            'attention.resolve',
            'attention.close',
            'attention.send-email',
            'attention.manage-notes',
            'attention.view-history',
            'attention.manage-departments',
            'attention.manage-types',
            'attention.manage-settings',
            'attention.view-reports',
        ]);
        $this->command->info('Rol creado: attention-settings');

        // Attention Manager - Can manage assigned attentions
        $attentionManager = Role::firstOrCreate(
            ['name' => 'attention-manager', 'guard_name' => 'web']
        );
        $attentionManager->givePermissionTo([
            'attention.view-all',
            'attention.create',
            'attention.update',
            'attention.manage',
            'attention.assign',
            'attention.change-status',
            'attention.resolve',
            'attention.close',
            'attention.send-email',
            'attention.manage-notes',
            'attention.view-history',
            'attention.view-reports',
        ]);
        $this->command->info('Rol creado: attention-manager');

        // Attention Agent - Can handle assigned attentions
        $attentionAgent = Role::firstOrCreate(
            ['name' => 'attention-agent', 'guard_name' => 'web']
        );
        $attentionAgent->givePermissionTo([
            'attention.view',
            'attention.create',
            'attention.update',
            'attention.change-status',
            'attention.resolve',
            'attention.send-email',
            'attention.manage-notes',
            'attention.view-history',
        ]);
        $this->command->info('Rol creado: attention-agent');

        // Attention User - Basic user (can create and view own)
        $attentionUser = Role::firstOrCreate(
            ['name' => 'attention-user', 'guard_name' => 'web']
        );
        $attentionUser->givePermissionTo([
            'attention.view',
            'attention.create',
        ]);
        $this->command->info('Rol creado: attention-user');

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Permisos y roles creados exitosamente!');
        $this->command->info('=================================================');
        $this->command->info('');
        $this->command->info('Roles creados:');
        $this->command->info('  - super-settings: Acceso completo');
        $this->command->info('  - attention-settings: Administrador del módulo');
        $this->command->info('  - attention-manager: Supervisor de peticiones');
        $this->command->info('  - attention-agent: Agente de atención');
        $this->command->info('  - attention-user: Usuario básico');
        $this->command->info('');
        $this->command->info('Para asignar un rol a un usuario:');
        $this->command->info('  $user->assignRole(\'attention-agent\');');
        $this->command->info('');
    }
}
