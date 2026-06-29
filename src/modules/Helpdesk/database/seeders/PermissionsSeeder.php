<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Acceso al módulo en el sidebar
            'modules.view.helpdesk',
            'modules.view.dashboard',

            // Módulo (acceso general)
            'helpdesk.view',
            'helpdesk.manage',

            // Conversations
            'helpdesk.conversations.view',
            'helpdesk.conversations.create',
            'helpdesk.conversations.reply',
            'helpdesk.conversations.update',
            'helpdesk.conversations.delete',
            'helpdesk.conversations.manage',
            'helpdesk.conversations.participants.manage',

            // Customers / Contactos
            'helpdesk.customers.view',
            'helpdesk.customers.create',
            'helpdesk.customers.update',
            'helpdesk.customers.delete',
            'helpdesk.customers.manage',
            'helpdesk.customers.merge',
            'helpdesk.customers.insights',

            // Companies (cuentas empresa)
            'helpdesk.companies.view',
            'helpdesk.companies.manage',

            // Agents
            'helpdesk.agents.manage',

            // Canned Replies (respuestas rápidas)
            'helpdesk.canned-replies.view',
            'helpdesk.canned-replies.create',
            'helpdesk.canned-replies.update',
            'helpdesk.canned-replies.delete',
            'helpdesk.canned-replies.manage',

            // Tags (etiquetas)
            'helpdesk.tags.view',
            'helpdesk.tags.create',
            'helpdesk.tags.update',
            'helpdesk.tags.delete',
            'helpdesk.tags.manage',

            // Statuses (estados de conversación)
            'helpdesk.statuses.view',
            'helpdesk.statuses.create',
            'helpdesk.statuses.update',
            'helpdesk.statuses.delete',
            'helpdesk.statuses.manage',

            // Conversation Views (vistas guardadas)
            'helpdesk.views.view',
            'helpdesk.views.create',
            'helpdesk.views.update',
            'helpdesk.views.delete',
            'helpdesk.views.manage',

            // Groups / Teams
            'helpdesk.groups.view',
            'helpdesk.groups.create',
            'helpdesk.groups.update',
            'helpdesk.groups.delete',
            'helpdesk.groups.manage',

            // Skills
            'helpdesk.skills.view',
            'helpdesk.skills.manage',

            // Webhooks
            'helpdesk.webhooks.view',
            'helpdesk.webhooks.create',
            'helpdesk.webhooks.update',
            'helpdesk.webhooks.delete',
            'helpdesk.webhooks.manage',

            // SLA Policies
            'helpdesk.sla-policies.view',
            'helpdesk.sla-policies.create',
            'helpdesk.sla-policies.update',
            'helpdesk.sla-policies.delete',

            // Custom Fields / Attributes
            'helpdesk.custom-fields.view',
            'helpdesk.custom-fields.manage',

            // Automation Rules
            'helpdesk.automation-rules.view',
            'helpdesk.automation-rules.create',
            'helpdesk.automation-rules.update',
            'helpdesk.automation-rules.delete',
            'helpdesk.automation-rules.manage',

            // Macros
            'helpdesk.macros.view',
            'helpdesk.macros.create',
            'helpdesk.macros.update',
            'helpdesk.macros.delete',
            'helpdesk.macros.manage',
            'helpdesk.macros.use',

            // Workflows
            'helpdesk.workflows.view',
            'helpdesk.workflows.manage',

            // Drip Campaigns
            'helpdesk.drip-campaigns.view',
            'helpdesk.drip-campaigns.manage',

            // Broadcasts (mensajes masivos)
            'helpdesk.broadcasts.view',
            'helpdesk.broadcasts.manage',

            // WhatsApp Templates (HSM)
            'helpdesk.whatsapp-templates.view',
            'helpdesk.whatsapp-templates.manage',

            // Brands / Marcas (inboxes multi-marca)
            'helpdesk.brands.view',
            'helpdesk.brands.manage',

            // Banners
            'helpdesk.banners.manage',

            // Surveys / Encuestas
            'helpdesk.surveys.manage',

            // Slack integration
            'helpdesk.slack.view',
            'helpdesk.slack.manage',

            // Schedule (turnos y guardias)
            'helpdesk.schedule.view',
            'helpdesk.schedule.create',
            'helpdesk.schedule.update',
            'helpdesk.schedule.delete',
            'helpdesk.schedule.manage',

            // Help Center (base de conocimiento)
            'helpdesk.helpcenter.view',
            'helpdesk.helpcenter.categories.view',
            'helpdesk.helpcenter.categories.create',
            'helpdesk.helpcenter.categories.update',
            'helpdesk.helpcenter.categories.delete',
            'helpdesk.helpcenter.categories.manage',
            'helpdesk.helpcenter.articles.view',
            'helpdesk.helpcenter.articles.create',
            'helpdesk.helpcenter.articles.update',
            'helpdesk.helpcenter.articles.delete',
            'helpdesk.helpcenter.articles.manage',

            // Reports & Metrics
            'helpdesk.metrics.view',
            'helpdesk.metrics.export',
            'helpdesk.reports.view',
            'helpdesk.exports.create',

            // Presence (estado del agente)
            'helpdesk.presence.manage',

            // AI features
            'helpdesk.ai.use',

            // Audit Log
            'helpdesk.audit.view',

            // Status Page (status components + incidents — distinct from conversation statuses)
            'helpdesk.status.manage',

            // Settings
            'helpdesk.settings.view',
            'helpdesk.settings.update',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
