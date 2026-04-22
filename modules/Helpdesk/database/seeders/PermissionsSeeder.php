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
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
