<?php

namespace Modules\Role\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListPermissionsCommand extends Command
{
    protected $signature = 'permissions:list {--role= : Filter by specific role} {--user= : Filter permissions for specific user}';

    protected $description = 'List all permissions and their role assignments';

    public function handle()
    {
        if ($this->option('user')) {
            $this->showUserPermissions($this->option('user'));
        } elseif ($this->option('role')) {
            $this->showRolePermissions($this->option('role'));
        } else {
            $this->showAllPermissions();
        }
    }

    /**
     * Show all permissions
     */
    protected function showAllPermissions()
    {
        $permissions = Permission::all();

        if ($permissions->isEmpty()) {
            $this->warn('⚠️  No permissions found. Run: php artisan permissions:create');

            return;
        }

        $this->info('📋 All Permissions ('.$permissions->count().'):');
        $this->newLine();

        $rows = [];
        foreach ($permissions as $permission) {
            $roleCount = $permission->roles()->count();
            $rows[] = [
                'id' => $permission->id,
                'permission' => $permission->name,
                'roles' => $roleCount,
            ];
        }

        $this->table(['ID', 'Permission Name', 'Assigned to Roles'], $rows);
    }

    /**
     * Show permissions for a specific role
     */
    protected function showRolePermissions($roleName)
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            $this->error("❌ Role '{$roleName}' not found");

            return;
        }

        $permissions = $role->permissions;

        if ($permissions->isEmpty()) {
            $this->warn("⚠️  Role '{$roleName}' has no permissions assigned");

            return;
        }

        $this->info("📋 Permissions for Role: <fg=cyan>{$roleName}</fg=cyan>");
        $this->line("   Total: {$permissions->count()} permissions\n");

        $rows = [];
        foreach ($permissions as $permission) {
            $rows[] = [
                'id' => $permission->id,
                'permission' => $permission->name,
            ];
        }

        $this->table(['ID', 'Permission'], $rows);
    }

    /**
     * Show permissions for a specific user
     */
    protected function showUserPermissions($userId)
    {
        $user = \App\Models\User::find($userId);

        if (! $user) {
            $this->error("❌ User with ID {$userId} not found");

            return;
        }

        $roles = $user->roles;
        $directPermissions = $user->permissions;
        $inheritedPermissions = $user->getPermissionsViaRoles();

        $this->info("👤 Permissions for User: <fg=cyan>{$user->email}</fg=cyan>");
        $this->newLine();

        // Show roles
        $this->line('<fg=blue>Roles:</fg=blue>');
        if ($roles->isEmpty()) {
            $this->line('  (no roles)');
        } else {
            foreach ($roles as $role) {
                $this->line("  • {$role->name}");
            }
        }
        $this->newLine();

        // Show direct permissions
        $this->line('<fg=blue>Direct Permissions:</fg=blue>');
        if ($directPermissions->isEmpty()) {
            $this->line('  (none)');
        } else {
            foreach ($directPermissions as $permission) {
                $this->line("  • {$permission->name}");
            }
        }
        $this->newLine();

        // Show inherited permissions from roles
        $this->line('<fg=blue>Permissions from Roles (Total: '.$inheritedPermissions->count().'):</fg=blue>');
        if ($inheritedPermissions->isEmpty()) {
            $this->line('  (none)');
        } else {
            $rows = [];
            foreach ($inheritedPermissions as $permission) {
                $rows[] = [
                    'permission' => $permission->name,
                ];
            }
            $this->table(['Permission'], $rows);
        }
    }
}
