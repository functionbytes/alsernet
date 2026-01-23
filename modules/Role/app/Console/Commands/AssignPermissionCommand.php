<?php

namespace Modules\Role\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssignPermissionCommand extends Command
{
    protected $signature = 'permissions:assign
                            {target_id : User ID or Role ID}
                            {permission : Permission name}
                            {--role : Assign to role instead of user}';

    protected $description = 'Assign a permission to a user or role';

    public function handle()
    {
        $targetId = $this->argument('target_id');
        $permissionName = $this->argument('permission');
        $isRole = $this->option('role');

        // Find permission
        $permission = Permission::where('name', $permissionName)->first();
        if (! $permission) {
            $this->error("❌ Permission '{$permissionName}' not found");
            $this->showAvailablePermissions();

            return 1;
        }

        if ($isRole) {
            // Assign to role
            return $this->assignToRole($targetId, $permission);
        } else {
            // Assign to user
            return $this->assignToUser($targetId, $permission);
        }
    }

    /**
     * Assign permission to user
     */
    protected function assignToUser($userId, $permission)
    {
        $user = User::find($userId);
        if (! $user) {
            $this->error("❌ User with ID {$userId} not found");

            return 1;
        }

        // Check if already has permission
        if ($user->hasPermissionTo($permission->name)) {
            $this->info("ℹ️  User '{$user->email}' already has permission '{$permission->name}'");

            return 0;
        }

        // Assign permission
        $user->givePermissionTo($permission->name);

        $this->info("✅ Permission '{$permission->name}' assigned to {$user->email}");
        $this->line('   Total permissions: '.$user->getAllPermissions()->count());

        return 0;
    }

    /**
     * Assign permission to role
     */
    protected function assignToRole($roleId, $permission)
    {
        $role = Role::find($roleId);
        if (! $role) {
            $this->error("❌ Role with ID {$roleId} not found");

            return 1;
        }

        // Check if already has permission
        if ($role->hasPermissionTo($permission->name)) {
            $this->info("ℹ️  Role '{$role->name}' already has permission '{$permission->name}'");

            return 0;
        }

        // Assign permission
        $role->givePermissionTo($permission->name);

        $this->info("✅ Permission '{$permission->name}' assigned to role '{$role->name}'");
        $this->line('   Total permissions: '.$role->permissions()->count());

        return 0;
    }

    /**
     * Show available permissions
     */
    protected function showAvailablePermissions()
    {
        $this->newLine();
        $this->info('📋 Available permissions:');

        $permissions = Permission::limit(10)->pluck('name');
        foreach ($permissions as $permission) {
            $this->line("  • {$permission}");
        }

        if (Permission::count() > 10) {
            $this->line('  ... and '.(Permission::count() - 10).' more');
        }
    }
}
