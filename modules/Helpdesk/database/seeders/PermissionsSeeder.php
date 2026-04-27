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
            // Modulo
            'helpdesk.view', 'helpdesk.manage',
            // Customers
            'helpdesk.customers.view', 'helpdesk.customers.create', 'helpdesk.customers.update', 'helpdesk.customers.delete', 'helpdesk.customers.manage',
            // Conversations
            'helpdesk.conversations.view', 'helpdesk.conversations.create', 'helpdesk.conversations.update', 'helpdesk.conversations.delete', 'helpdesk.conversations.manage',
            // Metrics
            'helpdesk.metrics.view', 'helpdesk.metrics.export',
            // Canned replies
            'helpdesk.cannedreplies.view', 'helpdesk.cannedreplies.create', 'helpdesk.cannedreplies.update', 'helpdesk.cannedreplies.delete', 'helpdesk.cannedreplies.manage',
            // Settings
            'helpdesk.settings.view', 'helpdesk.settings.update',
            // Tags
            'helpdesk.tags.view', 'helpdesk.tags.create', 'helpdesk.tags.update', 'helpdesk.tags.delete', 'helpdesk.tags.manage',
            // Statuses
            'helpdesk.statuses.view', 'helpdesk.statuses.create', 'helpdesk.statuses.update', 'helpdesk.statuses.delete', 'helpdesk.statuses.manage',
            // Views
            'helpdesk.views.view', 'helpdesk.views.create', 'helpdesk.views.update', 'helpdesk.views.delete', 'helpdesk.views.manage',
            // Groups
            'helpdesk.groups.view', 'helpdesk.groups.create', 'helpdesk.groups.update', 'helpdesk.groups.delete', 'helpdesk.groups.manage',
            // Webhooks
            'helpdesk.webhooks.view', 'helpdesk.webhooks.create', 'helpdesk.webhooks.update', 'helpdesk.webhooks.delete', 'helpdesk.webhooks.manage',
            // Schedule (shifts & on-call)
            'helpdesk.schedule.view', 'helpdesk.schedule.create', 'helpdesk.schedule.update', 'helpdesk.schedule.delete', 'helpdesk.schedule.manage',
            // Help center categories
            'helpdesk.helpcenter.categories.view', 'helpdesk.helpcenter.categories.create', 'helpdesk.helpcenter.categories.update', 'helpdesk.helpcenter.categories.delete', 'helpdesk.helpcenter.categories.manage',
            // Custom attributes
            'helpdesk.attributes.view', 'helpdesk.attributes.create', 'helpdesk.attributes.update', 'helpdesk.attributes.delete', 'helpdesk.attributes.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
