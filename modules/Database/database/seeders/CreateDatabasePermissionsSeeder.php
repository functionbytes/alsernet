<?php

namespace Modules\Database\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateDatabasePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds database-related permissions for Spatie Laravel Permission.
     * Creates granular permissions for database configuration and cleanup features
     * and assigns them to predefined roles (super-admin, manager).
     *
     * Permissions are organized by feature area for easy maintenance and scalability.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all database permissions
        $permissions = $this->definePermissions();

        // Create permissions in database
        $this->createPermissionsFor($permissions);

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('✅ Database permissions seeded successfully');
    }

    /**
     * Define all database-related permissions.
     *
     * Structure: 'permission.name' => 'Human readable description'
     * Organized by feature area for easy management.
     */
    private function definePermissions(): array
    {
        return [
            // Database Settings
            'database.backups.view' => 'Ver configuración de base de datos',
            'database.backups.update' => 'Actualizar configuración de base de datos',
            'database.backups.test_connection' => 'Probar conexión a base de datos',

            // Database Cleanup
            'database.cleanup.view' => 'Ver herramienta de limpieza de base de datos',
            'database.cleanup.truncate' => 'Truncar tablas de base de datos',
            'database.cleanup.get_table_count' => 'Obtener conteo de registros de tablas',
        ];
    }

    /**
     * Create or find permissions in the database.
     *
     * Uses findOrCreate to be idempotent - safe to run multiple times.
     *
     * @param  array  $permissions  Array of permission_name => description
     */
    private function createPermissionsFor(array $permissions): void
    {
        foreach ($permissions as $permissionName => $description) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $this->command->line('Created '.count($permissions).' permissions');
    }

    /**
     * Assign permissions to database-related roles.
     *
     * Defines role levels:
     * - super-admin: All permissions (via wildcard)
     * - manager: View permissions only
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - All permissions
        $this->assignToRole('super-admin', [
            'database.*',
        ]);

        // Manager - View only
        $this->assignToRole('manager', [
            'database.backups.view',
            'database.cleanup.view',
        ]);
    }

    /**
     * Helper method to assign permissions to a role.
     *
     * Supports wildcard permissions (e.g., 'database.*').
     * Safely handles cases where role doesn't exist yet.
     *
     * @param  string  $roleName  The role name
     * @param  array  $permissions  Array of permission names (supports wildcards)
     */
    private function assignToRole(string $roleName, array $permissions): void
    {
        $role = Role::findOrCreate($roleName, 'web');

        foreach ($permissions as $permission) {
            // Handle wildcard permissions (e.g., 'database.*')
            if (str_ends_with($permission, '.*')) {
                $prefix = substr($permission, 0, -2);
                $matchingPermissions = Permission::query()
                    ->where('name', 'like', $prefix.'.%')
                    ->get();

                $role->givePermissionTo($matchingPermissions);
            } else {
                // Regular permission
                $role->givePermissionTo($permission);
            }
        }

        $this->command->line("Assigned permissions to role: {$roleName}");
    }
}
