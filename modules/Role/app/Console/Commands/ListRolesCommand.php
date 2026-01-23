<?php

namespace Modules\Role\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class ListRolesCommand extends Command
{
    protected $signature = 'roles:list {--users : Show users with their roles}';

    protected $description = 'List all roles and optionally show users with their roles';

    public function handle()
    {
        $this->showRoles();

        if ($this->option('users')) {
            $this->newLine();
            $this->showUsersWithRoles();
        }
    }

    /**
     * Show all available roles
     */
    protected function showRoles()
    {
        $roles = Role::all();

        if ($roles->isEmpty()) {
            $this->warn('⚠️  No roles found. Run: php artisan roles:create');

            return;
        }

        $this->info('📋 All Roles:');
        $this->newLine();

        $rows = [];
        foreach ($roles as $role) {
            $userCount = $role->users()->count();
            $rows[] = [
                'id' => $role->id,
                'role' => $role->name,
                'label' => $role->label ?? '-',
                'users' => $userCount,
            ];
        }

        $this->table(['ID', 'Role Name', 'Label', 'Users Assigned'], $rows);

        // Show descriptions
        $this->newLine();
        $this->info('📖 Role Descriptions:');
        $this->newLine();

        foreach ($roles as $role) {
            $colorCode = $role->color ?? '#CCCCCC';
            $this->line("<fg=$colorCode>■</> <fg=cyan>{$role->name}</fg=cyan>");
            $this->line("   Label: {$role->label}");
            if ($role->description) {
                $this->line("   Description: {$role->description}");
            }
            $this->newLine();
        }
    }

    /**
     * Show users with their assigned roles
     */
    protected function showUsersWithRoles()
    {
        $users = User::with('roles')->get();

        if ($users->isEmpty()) {
            $this->warn('⚠️  No users found');

            return;
        }

        $this->info('👥 Users with Roles:');
        $this->newLine();

        $rows = [];
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->implode(', ');
            $roles = $roles ?: '(no roles)';

            $rows[] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name ?? '-',
                'roles' => $roles,
            ];
        }

        $this->table(['ID', 'Email', 'Name', 'Roles'], $rows);
    }
}
