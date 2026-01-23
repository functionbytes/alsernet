<?php

namespace Modules\Role\Console\Commands;

use Illuminate\Console\Command;
use Modules\Role\Models\AppRoute;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreatePermissionsCommand extends Command
{
    protected $signature = 'permissions:create {--assign : Assign permissions to roles}';

    protected $description = 'Create permissions based on synced routes and optionally assign to roles';

    public function handle()
    {
        $this->info('🔐 Creating permissions from synced routes...\n');

        // Get all active routes
        $routes = AppRoute::where('is_active', true)->get();

        if ($routes->isEmpty()) {
            $this->warn('⚠️  No routes found. Run: php artisan routes:sync');

            return 1;
        }

        $createdPermissions = [];
        $existingPermissions = [];

        // Create permission for each route
        foreach ($routes as $route) {
            $permissionName = $route->name; // Use route name as permission

            try {
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName],
                    ['guard_name' => 'web']
                );

                if ($permission->wasRecentlyCreated) {
                    $createdPermissions[] = $permissionName;
                    $this->line("  ✓ Created: <fg=green>{$permissionName}</fg=green> ({$route->method} {$route->path})");
                } else {
                    $existingPermissions[] = $permissionName;
                }
            } catch (\Exception $e) {
                $this->line("  ✗ Error creating {$permissionName}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line('  ✓ Created: '.count($createdPermissions).' new permission(s)');
        $this->line('  ℹ Already existed: '.count($existingPermissions).' permission(s)');
        $this->line('  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('  Total permissions: '.(count($createdPermissions) + count($existingPermissions)));

        // Optionally assign permissions to roles
        if ($this->option('assign')) {
            $this->newLine();
            $this->assignPermissionsToRoles($routes);
        } else {
            $this->newLine();
            $this->comment('💡 To assign permissions to roles, run: php artisan permissions:create --assign');
        }

        $this->newLine();
        $this->info('✅ Permission creation completed!');

        return 0;
    }

    /**
     * Assign permissions to roles based on profile
     */
    protected function assignPermissionsToRoles($routes)
    {
        $this->info('🔗 Assigning permissions to roles...\n');

        // Group routes by profile
        $routesByProfile = $routes->groupBy('profile');

        // Role-to-profile mapping (using plural role names)
        $roleProfileMapping = [
            'super-admins' => ['manager', 'callcenter', 'inventory', 'warehouse', 'shop', 'administrative'],
            'admins' => ['manager', 'callcenter', 'inventory', 'warehouse', 'shop', 'administrative'],
            'theme' => ['manager'],
            'callcenters' => ['callcenter'],
            'shops' => ['shop'],
            'warehouses' => ['inventory', 'warehouse'],
            'supports' => ['callcenter'],
            'administratives' => ['administrative'],
        ];

        $assignedCount = 0;

        foreach ($roleProfileMapping as $roleName => $profiles) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                $this->line("  ⚠️  Role '{$roleName}' not found. Create roles first: php artisan roles:create");

                continue;
            }

            // Get all permissions for the profiles this role can access
            $permissions = [];
            foreach ($profiles as $profile) {
                if ($routesByProfile->has($profile)) {
                    foreach ($routesByProfile[$profile] as $route) {
                        $permissions[] = $route->name;
                    }
                }
            }

            if (empty($permissions)) {
                $this->line("  ℹ  Role '{$roleName}' has no routes to assign");

                continue;
            }

            // Assign permissions to role
            try {
                $role->syncPermissions($permissions);
                $permCount = count($permissions);
                $this->line("  ✓ Role '<fg=cyan>{$roleName}</fg=cyan>' → {$permCount} permissions assigned");
                $assignedCount += $permCount;
            } catch (\Exception $e) {
                $this->line("  ✗ Error assigning to '{$roleName}': {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("  Total assignments: {$assignedCount}");
    }
}
