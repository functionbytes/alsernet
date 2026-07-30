<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskRolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createAgentRole();
        $this->createAdminRole();
    }

    private function createAgentRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'helpdesk-agent', 'guard_name' => 'web'],
            ['description' => 'Agente de helpdesk: gestiona conversaciones y clientes asignados.']
        );

        $permissions = [
            // Acceso al módulo
            'helpdesk.view',

            // Conversaciones
            'helpdesk.conversations.view',
            'helpdesk.conversations.create',
            'helpdesk.conversations.reply',
            'helpdesk.conversations.update',
            'helpdesk.conversations.participants.manage',

            // Clientes
            'helpdesk.customers.view',
            'helpdesk.customers.create',
            'helpdesk.customers.update',
            'helpdesk.customers.insights',

            // Respuestas rápidas
            'helpdesk.canned-replies.view',

            // Etiquetas
            'helpdesk.tags.view',

            // Vistas guardadas
            'helpdesk.views.view',
            'helpdesk.views.create',

            // Presence
            'helpdesk.presence.manage',

            // IA
            'helpdesk.ai.use',

            // Macros (solo uso)
            'helpdesk.macros.view',
            'helpdesk.macros.use',

            // Métricas propias
            'helpdesk.metrics.view',

            // Help Center (lectura)
            'helpdesk.helpcenter.view',
            'helpdesk.helpcenter.categories.view',
            'helpdesk.helpcenter.articles.view',
        ];

        $role->syncPermissions(
            Permission::whereIn('name', $permissions)->where('guard_name', 'web')->get()
        );
    }

    private function createAdminRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'helpdesk-admin', 'guard_name' => 'web'],
            ['description' => 'Administrador de helpdesk: acceso completo a todas las funcionalidades.']
        );

        $permissions = Permission::where('name', 'like', 'helpdesk.%')
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $role->syncPermissions(
            Permission::whereIn('name', $permissions)->where('guard_name', 'web')->get()
        );
    }
}
