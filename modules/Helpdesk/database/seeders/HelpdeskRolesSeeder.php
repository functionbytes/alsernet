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
        $this->createRestrictedAgentRole();
        $this->createSupervisorRole();
        $this->createAdminRole();
    }

    /**
     * Base compartida por los 3 perfiles "de agente" (estándar, restringido,
     * supervisor): ver/responder conversaciones y clientes, y el resto del
     * funcionamiento normal del inbox (adjuntos/imágenes van con
     * conversations.reply+update — no hay un permiso aparte para adjuntar).
     * Cada rol solo añade o quita visibilidad sobre CUÁLES conversaciones.
     *
     * @return string[]
     */
    private function agentPermissions(): array
    {
        return [
            // Acceso al módulo
            'helpdesk.view',
            // Sin esto no aparece el icono de Helpdesk en el menú lateral —
            // se descubrió que ni siquiera 'helpdesk-agent' (12 usuarios
            // reales) lo tenía, así que probablemente nunca vieron el icono.
            'modules.view.helpdesk',
            // Búsqueda global del helpdesk: permiso propio, separado de
            // helpdesk.view, para poder quitárselo a un perfil sin sacarlo
            // también del módulo (ver createSupervisorRole()).
            'helpdesk.search.use',

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
    }

    private function createAgentRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'helpdesk-agent', 'guard_name' => 'web'],
            ['description' => 'Agente de helpdesk: ve y gestiona todas las conversaciones de sus bandejas asignadas.']
        );

        $role->syncPermissions(
            Permission::whereIn('name', $this->agentPermissions())->where('guard_name', 'web')->get()
        );
    }

    /**
     * Mismo perfil que 'helpdesk-agent' pero solo ve/responde conversaciones
     * con assignee_id = el mismo — nunca las sin asignar ni las de otro
     * agente de su misma bandeja. Ver ConversationPolicy::isRestrictedToOwn
     * y ConversationsController::isRestrictedToOwnConversations.
     */
    private function createRestrictedAgentRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'helpdesk-agent-restricted', 'guard_name' => 'web'],
            ['description' => 'Agente de helpdesk: solo ve y gestiona las conversaciones asignadas a él mismo.']
        );

        $permissions = [
            ...$this->agentPermissions(),
            'helpdesk.conversations.view-assigned-only',
        ];

        $role->syncPermissions(
            Permission::whereIn('name', $permissions)->where('guard_name', 'web')->get()
        );
    }

    /**
     * Ve y gestiona TODAS las bandejas/agentes (como helpdesk.manage a
     * efectos de visibilidad de conversaciones), pero sin acceso a Settings,
     * Webhooks, Automatizaciones, Integraciones, etc. — eso sigue exigiendo
     * 'helpdesk-admin'.
     */
    private function createSupervisorRole(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'helpdesk-supervisor', 'guard_name' => 'web'],
            ['description' => 'Supervisor de helpdesk: ve y reasigna todas las conversaciones de todas las bandejas, sin acceso a la configuración del módulo.']
        );

        // Perfil "solo conversaciones": ni siquiera las lecturas de la base
        // de agente que no tienen que ver con gestionar conversaciones
        // (métricas propias, centro de ayuda, buscador global) — un
        // supervisor reasigna y modera conversaciones de todo el equipo, no
        // consulta reportes ni la base de conocimiento desde este rol.
        $excluded = [
            'helpdesk.metrics.view',
            'helpdesk.helpcenter.view',
            'helpdesk.helpcenter.categories.view',
            'helpdesk.helpcenter.articles.view',
            'helpdesk.search.use',
        ];

        $permissions = [
            ...array_diff($this->agentPermissions(), $excluded),
            'helpdesk.conversations.view-all',
            // Gestión ampliada: reasignar/cerrar/fusionar cualquier
            // conversación, no solo las suyas.
            'helpdesk.conversations.manage',
            'helpdesk.conversations.delete',
            'helpdesk.customers.manage',
            'helpdesk.customers.merge',
            'helpdesk.canned-replies.manage',
            'helpdesk.tags.manage',
            'helpdesk.macros.manage',
            'helpdesk.groups.view',
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

        $permissions = Permission::where('guard_name', 'web')
            ->where(function ($query) {
                $query->where('name', 'like', 'helpdesk.%')
                    // No empiezan por 'helpdesk.' pero son funcionalidad del
                    // módulo: visibilidad en el menú, alertas operativas
                    // (SendSlaBreachNotification y similares) y el modal
                    // "Permisos del rol" del inbox. Sin esto, 'helpdesk-admin'
                    // se queda sin icono de menú pese a decir "acceso
                    // completo a todas las funcionalidades".
                    ->orWhereIn('name', [
                        'modules.view.helpdesk',
                        'manage_helpdesk',
                        'roles.permissions.view',
                        'roles.permissions.manage',
                    ]);
            })
            ->pluck('name')
            ->toArray();

        $role->syncPermissions(
            Permission::whereIn('name', $permissions)->where('guard_name', 'web')->get()
        );
    }
}
