<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailrelayPermissionsSeeder extends Seeder
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
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.update',
            'mailrelay.campaigns.delete',
            'mailrelay.campaigns.send',
            'mailrelay.campaigns.duplicate',
            'mailrelay.campaigns.analytics',

            // Subscribers
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.update',
            'mailrelay.subscribers.delete',
            'mailrelay.subscribers.manage',
            'mailrelay.subscribers.import',
            'mailrelay.subscribers.export',

            // Imports
            'mailrelay.imports.create',
            'mailrelay.imports.view',
            'mailrelay.imports.process',
            'mailrelay.imports.delete',

            // Lists
            'mailrelay.lists.view',
            'mailrelay.lists.create',
            'mailrelay.lists.update',
            'mailrelay.lists.delete',

            // Validation
            'mailrelay.validation.test',
            'mailrelay.validation.validate',

            // Settings (Admin only)
            'mailrelay.settings.general',
            'mailrelay.settings.api',
            'mailrelay.settings.templates',
            'mailrelay.settings.groups',
            'mailrelay.settings.custom-fields',
            'mailrelay.settings.automations',
            'mailrelay.settings.webhooks',
            'mailrelay.settings.permissions',
            'mailrelay.settings.manage',

            // Dashboard
            'mailrelay.access',
            'mailrelay.dashboard.view',
        ];
    }

    private function assignToRoles(): void
    {
        // Super admin - all permissions
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        // Manager - operational + templates
        $manager = Role::findOrCreate('manager', 'web');
        $manager->givePermissionTo([
            'mailrelay.access',
            'mailrelay.dashboard.view',
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.update',
            'mailrelay.campaigns.send',
            'mailrelay.campaigns.duplicate',
            'mailrelay.campaigns.analytics',
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.update',
            'mailrelay.subscribers.import',
            'mailrelay.subscribers.export',
            'mailrelay.imports.create',
            'mailrelay.imports.view',
            'mailrelay.imports.process',
            'mailrelay.lists.view',
            'mailrelay.lists.create',
            'mailrelay.lists.update',
            'mailrelay.validation.test',
            'mailrelay.validation.validate',
            'mailrelay.settings.templates',
        ]);

        // Administrative - limited access
        $administrative = Role::findOrCreate('administrative', 'web');
        $administrative->givePermissionTo([
            'mailrelay.access',
            'mailrelay.campaigns.view',
            'mailrelay.subscribers.view',
            'mailrelay.lists.view',
        ]);
    }
}
