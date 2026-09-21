<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Acceso al módulo en el sidebar
            'modules.view.helpdesk' => 'Ver módulo de Helpdesk',
            'modules.view.dashboard' => 'Ver módulo de Dashboard',

            // Módulo (acceso general)
            'helpdesk.view' => 'Acceder al helpdesk',
            'helpdesk.manage' => 'Administración completa del helpdesk (incluye Settings)',

            // Conversations
            'helpdesk.conversations.view' => 'Ver conversaciones',
            'helpdesk.conversations.create' => 'Crear conversaciones',
            'helpdesk.conversations.reply' => 'Responder conversaciones',
            'helpdesk.conversations.update' => 'Actualizar conversaciones',
            'helpdesk.conversations.delete' => 'Eliminar conversaciones',
            'helpdesk.conversations.manage' => 'Gestionar conversaciones completamente',
            'helpdesk.conversations.participants.manage' => 'Gestionar participantes de la conversación',
            'helpdesk.conversations.link-customer' => 'Vincular cliente a la conversación',

            // Visibilidad de conversaciones (perfiles, 21-sep-2026): por
            // defecto un agente ve TODAS las conversaciones de las bandejas
            // que tiene asignadas por AgentInboxCapacity (helpdesk-agent). Estos
            // dos matizan ese comportamiento sin tocar 'helpdesk.manage' (que
            // sigue siendo el gate de administración completa, incluye
            // Settings) — ver ConversationPolicy y
            // ConversationsController::getUserInboxIds/buildFilteredConversationsQuery.
            //
            // view-all: ve TODAS las bandejas (como helpdesk.manage a efectos
            // de visibilidad de conversaciones), pero sin acceso a Settings /
            // Webhooks / Automatizaciones / etc. — rol 'helpdesk-supervisor'.
            'helpdesk.conversations.view-all' => 'Ver conversaciones de todas las bandejas',
            // view-assigned-only: dentro de sus bandejas, SOLO ve conversaciones
            // con assignee_id = el propio usuario (no ve sin asignar ni las de
            // otros agentes) — rol 'helpdesk-agent-restricted'.
            'helpdesk.conversations.view-assigned-only' => 'Ver solo conversaciones asignadas a uno mismo',

            // Customers / Contactos
            'helpdesk.customers.view' => 'Ver clientes',
            'helpdesk.customers.create' => 'Crear clientes',
            'helpdesk.customers.update' => 'Actualizar clientes',
            'helpdesk.customers.delete' => 'Eliminar clientes',
            'helpdesk.customers.manage' => 'Gestionar clientes completamente',
            'helpdesk.customers.merge' => 'Fusionar clientes duplicados',
            'helpdesk.customers.insights' => 'Ver estadísticas del cliente',

            // Companies (cuentas empresa)
            'helpdesk.companies.view' => 'Ver empresas',
            'helpdesk.companies.manage' => 'Gestionar empresas',

            // Documents (gestión de expedientes KYC desde el inbox —
            // aprobar/rechazar/enviar/subir/eliminar; separado de solo ver
            // la conversación).
            'helpdesk.documents.manage' => 'Gestionar expedientes de documentos del cliente',

            // Vincular a una conversación un expediente KYC cuyo email/teléfono
            // NO coincide con el cliente (búsqueda global + asignación manual
            // "a la fuerza"). Deliberadamente NO se otorga a nadie por defecto:
            // solo roles de confianza elevada deben poder saltarse el match de
            // pertenencia, y cada uso queda auditado en el historial del expediente.
            'helpdesk.documents.force-link' => 'Vincular a la fuerza un expediente que no coincide con el cliente',

            // Agents
            'helpdesk.agents.manage' => 'Gestionar agentes del helpdesk',

            // Canned Replies (respuestas rápidas)
            'helpdesk.canned-replies.view' => 'Ver respuestas predefinidas',
            'helpdesk.canned-replies.create' => 'Crear respuestas predefinidas',
            'helpdesk.canned-replies.update' => 'Actualizar respuestas predefinidas',
            'helpdesk.canned-replies.delete' => 'Eliminar respuestas predefinidas',
            'helpdesk.canned-replies.manage' => 'Gestionar respuestas predefinidas completamente',

            // Tags (etiquetas)
            'helpdesk.tags.view' => 'Ver etiquetas',
            'helpdesk.tags.create' => 'Crear etiquetas',
            'helpdesk.tags.update' => 'Actualizar etiquetas',
            'helpdesk.tags.delete' => 'Eliminar etiquetas',
            'helpdesk.tags.manage' => 'Gestionar etiquetas completamente',

            // Statuses (estados de conversación)
            'helpdesk.statuses.view' => 'Ver estados de conversación',
            'helpdesk.statuses.create' => 'Crear estados de conversación',
            'helpdesk.statuses.update' => 'Actualizar estados de conversación',
            'helpdesk.statuses.delete' => 'Eliminar estados de conversación',
            'helpdesk.statuses.manage' => 'Gestionar estados de conversación completamente',

            // Conversation Views (vistas guardadas)
            'helpdesk.views.view' => 'Ver vistas guardadas',
            'helpdesk.views.create' => 'Crear vistas guardadas',
            'helpdesk.views.update' => 'Actualizar vistas guardadas',
            'helpdesk.views.delete' => 'Eliminar vistas guardadas',
            'helpdesk.views.manage' => 'Gestionar vistas guardadas completamente',

            // Groups / Teams
            'helpdesk.groups.view' => 'Ver grupos/equipos',
            'helpdesk.groups.create' => 'Crear grupos/equipos',
            'helpdesk.groups.update' => 'Actualizar grupos/equipos',
            'helpdesk.groups.delete' => 'Eliminar grupos/equipos',
            'helpdesk.groups.manage' => 'Gestionar grupos/equipos completamente',

            // Webhooks
            'helpdesk.webhooks.view' => 'Ver webhooks',
            'helpdesk.webhooks.create' => 'Crear webhooks',
            'helpdesk.webhooks.update' => 'Actualizar webhooks',
            'helpdesk.webhooks.delete' => 'Eliminar webhooks',
            'helpdesk.webhooks.manage' => 'Gestionar webhooks completamente',

            // SLA Policies
            'helpdesk.sla-policies.view' => 'Ver políticas de SLA',
            'helpdesk.sla-policies.create' => 'Crear políticas de SLA',
            'helpdesk.sla-policies.update' => 'Actualizar políticas de SLA',
            'helpdesk.sla-policies.delete' => 'Eliminar políticas de SLA',

            // Custom Fields / Attributes
            'helpdesk.custom-fields.view' => 'Ver campos personalizados',
            'helpdesk.custom-fields.manage' => 'Gestionar campos personalizados',

            // Atributos personalizados (CustomAttributePolicy/AttributesController
            // usan estos 5 nombres granulares, distintos de custom-fields.*
            // de arriba; nunca se habían sembrado, por lo que la pantalla de
            // Settings > Atributos daba 500 con hasPermissionTo()).
            'helpdesk.attributes.view' => 'Ver atributos personalizados',
            'helpdesk.attributes.create' => 'Crear atributos personalizados',
            'helpdesk.attributes.update' => 'Actualizar atributos personalizados',
            'helpdesk.attributes.delete' => 'Eliminar atributos personalizados',
            'helpdesk.attributes.manage' => 'Gestionar atributos personalizados completamente',

            // Automation Rules
            'helpdesk.automation-rules.view' => 'Ver reglas de automatización',
            'helpdesk.automation-rules.create' => 'Crear reglas de automatización',
            'helpdesk.automation-rules.update' => 'Actualizar reglas de automatización',
            'helpdesk.automation-rules.delete' => 'Eliminar reglas de automatización',
            'helpdesk.automation-rules.manage' => 'Gestionar reglas de automatización completamente',

            // Macros
            'helpdesk.macros.view' => 'Ver macros',
            'helpdesk.macros.create' => 'Crear macros',
            'helpdesk.macros.update' => 'Actualizar macros',
            'helpdesk.macros.delete' => 'Eliminar macros',
            'helpdesk.macros.manage' => 'Gestionar macros completamente',
            'helpdesk.macros.use' => 'Usar macros',

            // Workflows
            'helpdesk.workflows.view' => 'Ver flujos de trabajo',
            'helpdesk.workflows.manage' => 'Gestionar flujos de trabajo',

            // Drip Campaigns
            'helpdesk.drip-campaigns.view' => 'Ver campañas de goteo',
            'helpdesk.drip-campaigns.manage' => 'Gestionar campañas de goteo',

            // Broadcasts (mensajes masivos)
            'helpdesk.broadcasts.view' => 'Ver difusiones',
            'helpdesk.broadcasts.manage' => 'Gestionar difusiones',

            // WhatsApp Templates (HSM)
            'helpdesk.whatsapp-templates.view' => 'Ver plantillas de WhatsApp',
            'helpdesk.whatsapp-templates.manage' => 'Gestionar plantillas de WhatsApp',

            // Brands / Marcas (inboxes multi-marca)
            'helpdesk.brands.view' => 'Ver marcas',
            'helpdesk.brands.manage' => 'Gestionar marcas',

            // Banners
            'helpdesk.banners.manage' => 'Gestionar banners',

            // Surveys / Encuestas
            'helpdesk.surveys.manage' => 'Gestionar encuestas',

            // Slack integration
            'helpdesk.slack.view' => 'Ver integración con Slack',
            'helpdesk.slack.manage' => 'Gestionar integración con Slack',

            // Schedule (turnos y guardias)
            'helpdesk.schedule.view' => 'Ver turnos y guardias',
            'helpdesk.schedule.create' => 'Crear turnos y guardias',
            'helpdesk.schedule.update' => 'Actualizar turnos y guardias',
            'helpdesk.schedule.delete' => 'Eliminar turnos y guardias',
            'helpdesk.schedule.manage' => 'Gestionar turnos y guardias completamente',

            // Help Center (base de conocimiento)
            'helpdesk.helpcenter.view' => 'Ver centro de ayuda',
            'helpdesk.helpcenter.categories.view' => 'Ver categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.create' => 'Crear categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.update' => 'Actualizar categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.delete' => 'Eliminar categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.manage' => 'Gestionar categorías del centro de ayuda completamente',
            'helpdesk.helpcenter.articles.view' => 'Ver artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.create' => 'Crear artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.update' => 'Actualizar artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.delete' => 'Eliminar artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.manage' => 'Gestionar artículos del centro de ayuda completamente',

            // Reports & Metrics
            'helpdesk.metrics.view' => 'Ver métricas',
            'helpdesk.metrics.export' => 'Exportar métricas',
            'helpdesk.reports.view' => 'Ver informes',
            'helpdesk.exports.create' => 'Crear exportaciones',

            // Presence (estado del agente)
            'helpdesk.presence.manage' => 'Gestionar estado de presencia del agente',

            // AI features
            'helpdesk.ai.use' => 'Usar funciones de inteligencia artificial',

            // Audit Log
            'helpdesk.audit.view' => 'Ver auditoría del helpdesk',

            // Status Page (status components + incidents — distinct from conversation statuses)
            'helpdesk.status.manage' => 'Gestionar página de estado (componentes e incidentes)',

            // Settings
            'helpdesk.settings.view' => 'Ver configuración del helpdesk',
            'helpdesk.settings.update' => 'Actualizar configuración del helpdesk',

            // Permisos de rol desde el modal "Permisos del rol" del inbox
            // (RolePermissionsController). Ya existían en BD dados de alta a
            // mano, sin ningún seeder que los recreara; se formalizan aquí
            // para que sobrevivan a un entorno nuevo o a un borrado.
            'roles.permissions.view' => 'Ver permisos de un rol',
            'roles.permissions.manage' => 'Gestionar permisos de un rol',

            // Usado por SendSlaBreachNotification, CheckTicketMailReputationCommand
            // y CollectOpsMetricsCommand (HelpdeskTickets) para encontrar a quién
            // avisar de incidencias operativas. Ya existía dado de alta a mano.
            'manage_helpdesk' => 'Recibir alertas operativas del helpdesk (SLA, reputación de correo, métricas)',

            // El enlace "Búsqueda global" y sus rutas usaban el genérico
            // helpdesk.view (el mismo que exige entrar al panel), así que no
            // se podía dar acceso al helpdesk sin dar también la búsqueda.
            // Permiso propio para poder recortarlo por rol (p. ej. un
            // supervisor de "solo conversaciones" sin buscador global).
            'helpdesk.search.use' => 'Usar la búsqueda global del helpdesk',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $this->backfillDocumentsManage();
        $this->backfillModuleVisibility();
        $this->backfillSearchUse();
    }

    /**
     * `helpdesk.documents.manage` es nuevo: para no dejar sin acceso a quien ya
     * gestionaba expedientes, se otorga a todo rol que ya tuviera
     * `helpdesk.conversations.update` (el tramo de "escritura" del helpdesk).
     * Idempotente y seguro de re-ejecutar.
     */
    private function backfillDocumentsManage(): void
    {
        Role::whereHas('permissions', fn ($q) => $q->where('name', 'helpdesk.conversations.update'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('helpdesk.documents.manage'));
    }

    /**
     * Cualquier rol con permisos de helpdesk (p. ej. 'helpdesk-supervisor',
     * 'helpdesk-agent-restricted') necesita también 'modules.view.helpdesk'
     * para que el icono del módulo aparezca en el menú — si no, el usuario
     * tiene permisos de sobra pero no ve por dónde entrar. Se detectó porque
     * esos dos roles (creados en otro seeder) nunca lo tenían. Genérico a
     * propósito: cubre cualquier rol futuro con permisos de helpdesk que se
     * cree sin pensar en el permiso de visibilidad del módulo.
     */
    private function backfillModuleVisibility(): void
    {
        Role::whereHas('permissions', fn ($q) => $q->where('name', 'like', 'helpdesk%'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('modules.view.helpdesk'));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * `helpdesk.search.use` reemplaza a `helpdesk.view` como permiso del
     * enlace "Búsqueda global": se concede a todo rol que ya tuviera acceso
     * general al helpdesk, para no quitarle a nadie una función que ya usaba.
     * Excepción deliberada: 'helpdesk-supervisor', pensado para un perfil de
     * "solo conversaciones" que no debe tener buscador global.
     */
    private function backfillSearchUse(): void
    {
        Role::whereHas('permissions', fn ($q) => $q->where('name', 'helpdesk.view'))
            ->where('name', '!=', 'helpdesk-supervisor')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('helpdesk.search.use'));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
