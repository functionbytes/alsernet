<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ReviewsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all Reviews module permissions
        $permissions = [
            // Google Connections
            'reviews.connections.view' => 'View Google Business Profile connections',
            'reviews.connections.create' => 'Connect Google Business Profile accounts',
            'reviews.connections.delete' => 'Delete Google Business Profile connections',

            // Reviews Management
            'reviews.reviews.view' => 'View reviews',
            'reviews.reviews.export' => 'Export reviews data',

            // Moderation
            'reviews.moderate' => 'Moderate reviews (show/hide)',
            'reviews.moderate.featured' => 'Mark reviews as featured',

            // Replies
            'reviews.replies.create' => 'Create review replies',
            'reviews.replies.approve' => 'Approve review replies',
            'reviews.replies.publish' => 'Publish replies to Google',
            'reviews.replies.delete' => 'Delete review replies',

            // Templates
            'reviews.templates.view' => 'View reply templates',
            'reviews.templates.create' => 'Create reply templates',
            'reviews.templates.update' => 'Update reply templates',
            'reviews.templates.delete' => 'Delete reply templates',

            // Settings
            'reviews.settings.view' => 'View Reviews module settings',
            'reviews.settings.update' => 'Update Reviews module settings',
            'reviews.settings.manage' => 'Manage Reviews module settings',
        ];

        // Create all permissions
        foreach ($permissions as $permission => $description) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        // Super Admin - All permissions
        $superAdmin = Role::findByName('super-settings', 'web');
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::where('name', 'like', 'reviews.%')->get());
        }

        // Admin - All permissions except settings.manage
        $admin = Role::findByName('settings', 'web');
        if ($admin) {
            $admin->givePermissionTo(
                Permission::where('name', 'like', 'reviews.%')
                    ->where('name', '!=', 'reviews.settings.manage')
                    ->get()
            );
        }

        // Manager - View, moderate, reply (no approval/publish)
        $manager = Role::findByName('manager', 'web');
        if ($manager) {
            $manager->givePermissionTo([
                'reviews.connections.view',
                'reviews.reviews.view',
                'reviews.reviews.export',
                'reviews.moderate',
                'reviews.moderate.featured',
                'reviews.replies.create',
                'reviews.templates.view',
                'reviews.templates.create',
                'reviews.templates.update',
                'reviews.settings.view',
            ]);
        }
    }
}
