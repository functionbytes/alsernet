<?php

namespace Modules\Mailing\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailingPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign to roles
        $this->assignToRoles();
    }

    private function getPermissions(): array
    {
        return [
            // Campaigns
            'mailing.campaigns.view',
            'mailing.campaigns.create',
            'mailing.campaigns.update',
            'mailing.campaigns.delete',
            'mailing.campaigns.send',
            'mailing.campaigns.duplicate',
            'mailing.campaigns.analytics',

            // Subscribers
            'mailing.subscribers.view',
            'mailing.subscribers.create',
            'mailing.subscribers.update',
            'mailing.subscribers.delete',
            'mailing.subscribers.manage',
            'mailing.subscribers.import',
            'mailing.subscribers.export',

            // Imports
            'mailing.imports.create',
            'mailing.imports.view',
            'mailing.imports.process',
            'mailing.imports.delete',

            // Lists
            'mailing.lists.view',
            'mailing.lists.create',
            'mailing.lists.update',
            'mailing.lists.delete',

            // Validation
            'mailing.validation.test',
            'mailing.validation.validate',

            // Settings (Admin only)
            'mailing.settings.general',
            'mailing.settings.api',
            'mailing.settings.templates',
            'mailing.settings.groups',
            'mailing.settings.custom-fields',
            'mailing.settings.automations',
            'mailing.settings.webhooks',
            'mailing.settings.permissions',
            'mailing.settings.manage',

            // Sending Servers
            'mailing.settings.sending-servers.view',
            'mailing.settings.sending-servers.create',
            'mailing.settings.sending-servers.edit',
            'mailing.settings.sending-servers.delete',
            'mailing.settings.sending-servers.test',

            // Bounce Handlers
            'mailing.settings.bounce-handlers.view',
            'mailing.settings.bounce-handlers.create',
            'mailing.settings.bounce-handlers.edit',
            'mailing.settings.bounce-handlers.delete',
            'mailing.settings.bounce-handlers.test',

            // Feedback Handlers
            'mailing.settings.feedback-handlers.view',
            'mailing.settings.feedback-handlers.create',
            'mailing.settings.feedback-handlers.edit',
            'mailing.settings.feedback-handlers.delete',
            'mailing.settings.feedback-handlers.test',

            // Sub-Accounts
            'mailing.settings.sub-accounts.view',
            'mailing.settings.sub-accounts.create',
            'mailing.settings.sub-accounts.edit',
            'mailing.settings.sub-accounts.delete',
            'mailing.settings.sub-accounts.test',

            // Verification Servers
            'mailing.settings.verification-servers.view',
            'mailing.settings.verification-servers.create',
            'mailing.settings.verification-servers.edit',
            'mailing.settings.verification-servers.delete',
            'mailing.settings.verification-servers.test',

            // Dashboard
            'mailing.access',
            'mailing.dashboard.view',
        ];
    }

    private function assignToRoles(): void
    {
        // Super admin - all permissions
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - all settings permissions
        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo([
            // Settings - Sending Servers
            'mailing.settings.sending-servers.view',
            'mailing.settings.sending-servers.create',
            'mailing.settings.sending-servers.edit',
            'mailing.settings.sending-servers.delete',
            'mailing.settings.sending-servers.test',

            // Settings - Bounce Handlers
            'mailing.settings.bounce-handlers.view',
            'mailing.settings.bounce-handlers.create',
            'mailing.settings.bounce-handlers.edit',
            'mailing.settings.bounce-handlers.delete',
            'mailing.settings.bounce-handlers.test',

            // Settings - Feedback Handlers
            'mailing.settings.feedback-handlers.view',
            'mailing.settings.feedback-handlers.create',
            'mailing.settings.feedback-handlers.edit',
            'mailing.settings.feedback-handlers.delete',
            'mailing.settings.feedback-handlers.test',

            // Settings - Sub-Accounts
            'mailing.settings.sub-accounts.view',
            'mailing.settings.sub-accounts.create',
            'mailing.settings.sub-accounts.edit',
            'mailing.settings.sub-accounts.delete',
            'mailing.settings.sub-accounts.test',

            // Settings - Verification Servers
            'mailing.settings.verification-servers.view',
            'mailing.settings.verification-servers.create',
            'mailing.settings.verification-servers.edit',
            'mailing.settings.verification-servers.delete',
            'mailing.settings.verification-servers.test',
        ]);

        // Manager - operational + templates
        $manager = Role::findOrCreate('manager', 'web');
        $manager->givePermissionTo([
            'mailing.access',
            'mailing.dashboard.view',
            'mailing.campaigns.view',
            'mailing.campaigns.create',
            'mailing.campaigns.update',
            'mailing.campaigns.send',
            'mailing.campaigns.duplicate',
            'mailing.campaigns.analytics',
            'mailing.subscribers.view',
            'mailing.subscribers.create',
            'mailing.subscribers.update',
            'mailing.subscribers.import',
            'mailing.subscribers.export',
            'mailing.imports.create',
            'mailing.imports.view',
            'mailing.imports.process',
            'mailing.lists.view',
            'mailing.lists.create',
            'mailing.lists.update',
            'mailing.validation.test',
            'mailing.validation.validate',
            'mailing.settings.templates',
        ]);

        // Administrative - limited access
        $administrative = Role::findOrCreate('administrative', 'web');
        $administrative->givePermissionTo([
            'mailing.access',
            'mailing.campaigns.view',
            'mailing.subscribers.view',
            'mailing.lists.view',
        ]);
    }
}
