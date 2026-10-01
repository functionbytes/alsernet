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
            'helpdesk.ai-prompts.agent-actions' => 'Ejecutar acciones del catálogo IA desde la bandeja de conversaciones',
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

        // Los agentes: todo rol que ya puede ver conversaciones.
        Role::query()
            ->whereHas('permissions', fn ($query) => $query->where('name', 'helpdesk.conversations.view'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('helpdesk.ai-prompts.agent-actions'));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
