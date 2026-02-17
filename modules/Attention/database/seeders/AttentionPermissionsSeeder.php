<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AttentionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
                'description' => 'Ver PQRSF asignados o del departamento',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.view-all',
                'description' => 'Ver todos los PQRSF sin restricciones',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.create',
                'description' => 'Crear nuevos PQRSF',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.update',
                'description' => 'Actualizar PQRSF',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.delete',
                'description' => 'Eliminar PQRSF',
                'guard_name' => 'web',
            ],

            // Advanced permissions
            [
                'name' => 'attention.manage',
                'description' => 'Gestionar completamente PQRSF',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.assign',
                'description' => 'Asignar PQRSF a usuarios o departamentos',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.change-status',
                'description' => 'Cambiar estado de PQRSF',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.resolve',
                'description' => 'Resolver PQRSF',
                'guard_name' => 'web',
            ],
            [
                'name' => 'attention.close',
                'description' => 'Cerrar PQRSF',
                'guard_name' => 'web',
            ],

            // Communication permissions
            [
                'name' => 'attention.send-email',
                'description' => 'Enviar emails relacionados con PQRSF',
                'guard_name' => 'web',
            ],

            // Notes permissions
            [
                'name' => 'attention.manage-notes',
                'description' => 'Gestionar notas de PQRSF',
                'guard_name' => 'web',
            ],

            // History permissions
            [
                'name' => 'attention.view-history',
                'description' => 'Ver historial completo de PQRSF',
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
                'description' => 'Gestionar tipos de PQRSF',
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
            ['name' => 'super-settings', 'guard_name' => 'web'],
            ['description' => 'Super Administrador con acceso completo']
        );
        $this->command->info('Rol creado: super-settings');

        // Attention Administrator - Full module control
        $attentionAdmin = Role::firstOrCreate(
            ['name' => 'attention-settings', 'guard_name' => 'web'],
            ['description' => 'Administrador del módulo de atención']
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
            ['name' => 'attention-manager', 'guard_name' => 'web'],
            ['description' => 'Supervisor de PQRSF']
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
            ['name' => 'attention-agent', 'guard_name' => 'web'],
            ['description' => 'Agente de atención al cliente']
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
            ['name' => 'attention-user', 'guard_name' => 'web'],
            ['description' => 'Usuario básico del módulo']
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
        $this->command->info('  - attention-manager: Supervisor de PQRSF');
        $this->command->info('  - attention-agent: Agente de atención');
        $this->command->info('  - attention-user: Usuario básico');
        $this->command->info('');
        $this->command->info('Para asignar un rol a un usuario:');
        $this->command->info('  $user->assignRole(\'attention-agent\');');
        $this->command->info('');
    }
}
