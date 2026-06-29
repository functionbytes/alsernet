<?php

namespace Modules\Social\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SocialPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Post permissions
            'social.view-posts',
            'social.create-posts',
            'social.edit-posts',
            'social.delete-posts',
            'social.publish-posts',
            'social.approve-posts',
            'social.reject-posts',

            // Network-specific publish permissions
            'social.publish-post-facebook',
            'social.publish-post-instagram',
            'social.publish-post-twitter',
            'social.publish-post-linkedin',
            'social.publish-post-pinterest',
            'social.publish-post-tiktok',

            // Campaign permissions
            'social.view-campaigns',
            'social.create-campaigns',
            'social.edit-campaigns',
            'social.delete-campaigns',

            // Account permissions
            'social.view-accounts',
            'social.create-accounts',
            'social.edit-accounts',
            'social.delete-accounts',

            // Label permissions
            'social.view-labels',
            'social.create-labels',
            'social.edit-labels',
            'social.delete-labels',

            // Hashtag permissions
            'social.view-hashtags',
            'social.create-hashtags',
            'social.edit-hashtags',
            'social.delete-hashtags',

            // Short URL permissions
            'social.view-short-urls',
            'social.create-short-urls',
            'social.edit-short-urls',
            'social.delete-short-urls',

            // RSS Feed permissions
            'social.view-rss-feeds',
            'social.create-rss-feeds',
            'social.edit-rss-feeds',
            'social.delete-rss-feeds',

            // A/B Test permissions
            'social.view-ab-tests',
            'social.create-ab-tests',
            'social.edit-ab-tests',
            'social.delete-ab-tests',

            // Bulk Import permissions
            'social.view-bulk-imports',
            'social.delete-bulk-imports',

            // Analytics permissions
            'social.view-analytics',
            'social.export-analytics',

            // Template permissions
            'social.view-templates',
            'social.create-templates',
            'social.edit-templates',
            'social.delete-templates',

            // Advanced features
            'social.use-ai-generator',
            'social.translate-posts',
            'social.bulk-import',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Roles and assign permissions

        // Social Manager - Full access
        $socialManager = Role::firstOrCreate(['name' => 'social-manager', 'guard_name' => 'web']);
        $socialManager->givePermissionTo(Permission::all());

        // Content Creator - Create and edit only
        $contentCreator = Role::firstOrCreate(['name' => 'content-creator', 'guard_name' => 'web']);
        $contentCreator->givePermissionTo([
            'social.view-posts',
            'social.create-posts',
            'social.edit-posts',
            'social.view-campaigns',
            'social.view-templates',
            'social.use-ai-generator',
            'social.translate-posts',
        ]);

        // Content Approver - Review and publish
        $contentApprover = Role::firstOrCreate(['name' => 'content-approver', 'guard_name' => 'web']);
        $contentApprover->givePermissionTo([
            'social.view-posts',
            'social.approve-posts',
            'social.reject-posts',
            'social.publish-posts',
            'social.publish-post-facebook',
            'social.publish-post-instagram',
            'social.publish-post-twitter',
            'social.publish-post-linkedin',
            'social.publish-post-pinterest',
            'social.publish-post-tiktok',
        ]);

        // Analyst - View and export only
        $analyst = Role::firstOrCreate(['name' => 'social-analyst', 'guard_name' => 'web']);
        $analyst->givePermissionTo([
            'social.view-posts',
            'social.view-campaigns',
            'social.view-analytics',
            'social.export-analytics',
        ]);

        // Network Specialist - Specific network only
        $facebookSpecialist = Role::firstOrCreate(['name' => 'facebook-specialist', 'guard_name' => 'web']);
        $facebookSpecialist->givePermissionTo([
            'social.view-posts',
            'social.create-posts',
            'social.edit-posts',
            'social.publish-post-facebook',
        ]);
    }
}
