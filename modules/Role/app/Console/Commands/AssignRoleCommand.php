<?php

namespace Modules\Role\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignRoleCommand extends Command
{
    protected $signature = 'roles:assign {email} {role}';

    protected $description = 'Assign or change a role for a user. Usage: roles:assign user@email.com rolename';

    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        // Find user by email
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("❌ Usuario no encontrado: {$email}");

            return 1;
        }

        // Check if role exists
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $this->error("❌ Rol no existe: {$roleName}");
            $this->info("\n📋 Roles disponibles:");
            Role::all()->each(fn ($r) => $this->line("  • {$r->name}"));

            return 1;
        }

        // Get current role
        $currentRole = $user->roles->first()?->name ?? 'ninguno';

        // Assign role
        $user->syncRoles([$roleName]);

        $this->info('✅ Rol asignado exitosamente');
        $this->line("\n📊 Resumen:");
        $this->line("  Usuario: {$user->email}");
        $this->line("  Rol anterior: {$currentRole}");
        $this->line("  Rol nuevo: {$roleName}");

        // Show what this role can access
        $this->showAccessInfo($roleName);

        return 0;
    }

    private function showAccessInfo($roleName)
    {
        $this->newLine();
        $this->info('🔐 Permisos y acceso para este rol:');

        // Get permissions for this role
        $role = Role::where('name', $roleName)->first();
        if ($role && $role->permissions->count() > 0) {
            $this->line("  Permisos: {$role->permissions->count()} permisos asignados");
        }

        // Show which profiles this role can access
        $this->line("\n  Perfiles accesibles:");
        $roleMapping = \App\Models\Role\RoleMapping::getActive();
        foreach ($roleMapping as $profile => $roles) {
            if (in_array($roleName, $roles)) {
                $route = \App\Models\ProfileRoute::getRoute($profile);
                $this->line("  • {$profile} → {$route}");
            }
        }
    }
}
