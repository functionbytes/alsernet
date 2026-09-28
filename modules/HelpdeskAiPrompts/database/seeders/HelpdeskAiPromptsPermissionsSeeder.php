<?php

namespace Modules\HelpdeskAiPrompts\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskAiPromptsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'helpdesk.ai-prompts.view' => 'Ver la biblioteca de prompts del asistente IA',
            'helpdesk.ai-prompts.manage' => 'Gestionar la biblioteca de prompts del asistente IA',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
