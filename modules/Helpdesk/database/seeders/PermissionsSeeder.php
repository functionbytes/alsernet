<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage_helpdesk_settings',
            'access_helpdesk',
            'create_tickets',
            'view_all_tickets',
            'view_assigned_tickets',
            'edit_tickets',
            'delete_tickets',
            'assign_tickets',
            'close_tickets',
            'reopen_tickets',
            'merge_tickets',
            'view_internal_notes',
            'create_internal_notes',
            'manage_canned_replies',
            'view_reports',
            'export_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign permissions to roles
        $managerRole = Role::where('name', 'manager')->first();
        $agentRole = Role::where('name', 'helpdesk-agent')->first();

        if ($managerRole) {
            $managerRole->givePermissionTo([
                'manage_helpdesk_settings',
                'access_helpdesk',
                'view_all_tickets',
                'edit_tickets',
                'assign_tickets',
                'close_tickets',
                'reopen_tickets',
                'merge_tickets',
                'manage_canned_replies',
                'view_reports',
                'export_reports',
            ]);
        }

        if ($agentRole) {
            $agentRole->givePermissionTo([
                'access_helpdesk',
                'create_tickets',
                'view_assigned_tickets',
                'edit_tickets',
                'close_tickets',
                'reopen_tickets',
                'view_internal_notes',
                'create_internal_notes',
                'view_reports',
            ]);
        }
    }
}
