<?php

namespace Modules\Reverb\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateReverbPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds Reverb-related permissions for Spatie Laravel Permission.
     * Creates granular permissions for broadcasting, channel management,
     * and server monitoring.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Define all Reverb permissions
        $permissions = $this->definePermissions();

        // Create permissions in database
        $this->createPermissionsFor($permissions);

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('✅ Reverb permissions seeded successfully');
    }

    /**
     * Define all Reverb-related permissions.
     */
    private function definePermissions(): array
    {
        return [
            // Settings Management
            'reverb.backups.view' => 'Ver configuración de Reverb',
            'reverb.backups.update' => 'Actualizar configuración de Reverb',

            // Channel Management
            'reverb.channels.view' => 'Ver canales de broadcasting',
            'reverb.channels.broadcast' => 'Enviar eventos a canales',

            // Event Broadcasting
            'reverb.events.broadcast' => 'Emitir eventos en tiempo real',

            // Presence Tracking
            'reverb.presence.view' => 'Ver información de presencia',

            // Monitoring
            'reverb.monitoring.view' => 'Ver estadísticas de Reverb',
            'reverb.monitoring.debug' => 'Depurar conexiones Reverb',
        ];
    }

    /**
     * Create or find permissions in the database.
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
     * Assign permissions to roles.
     *
     * Defines role levels:
     * - super-admin: All permissions
     * - manager: View and broadcast permissions
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - All permissions
        $this->assignToRole('super-admin', [
            'reverb.*',
        ]);

        // Manager - Limited permissions
        $this->assignToRole('manager', [
            'reverb.backups.view',
            'reverb.channels.view',
            'reverb.events.broadcast',
            'reverb.presence.view',
            'reverb.monitoring.view',
        ]);
    }

    /**
     * Helper method to assign permissions to a role.
     *
     * Supports wildcard permissions (e.g., 'reverb.*').
     *
     * @param  string  $roleName  The role name
     * @param  array  $permissions  Array of permission names (supports wildcards)
     */
    private function assignToRole(string $roleName, array $permissions): void
    {
        $role = Role::findOrCreate($roleName, 'web');

        foreach ($permissions as $permission) {
            // Handle wildcard permissions
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
