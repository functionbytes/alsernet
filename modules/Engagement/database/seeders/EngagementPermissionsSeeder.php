<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class EngagementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Migrate old permissions: helpdesk.livechat.X.Y → engagement.X.Y
        // (preserves role assignments via updates)
        $renames = [
            'helpdesk.livechat.events.view' => 'engagement.events.view',
            'helpdesk.livechat.scores.view' => 'engagement.scores.view',
            'helpdesk.livechat.triggers.view' => 'engagement.triggers.view',
            'helpdesk.livechat.triggers.create' => 'engagement.triggers.create',
            'helpdesk.livechat.triggers.update' => 'engagement.triggers.update',
            'helpdesk.livechat.triggers.delete' => 'engagement.triggers.delete',
            'helpdesk.livechat.personalizations.view' => 'engagement.personalizations.view',
            'helpdesk.livechat.personalizations.create' => 'engagement.personalizations.create',
            'helpdesk.livechat.personalizations.update' => 'engagement.personalizations.update',
            'helpdesk.livechat.personalizations.delete' => 'engagement.personalizations.delete',
            'helpdesk.livechat.platforms.view' => 'engagement.platforms.view',
            'helpdesk.livechat.platforms.create' => 'engagement.platforms.create',
            'helpdesk.livechat.platforms.update' => 'engagement.platforms.update',
            'helpdesk.livechat.platforms.delete' => 'engagement.platforms.delete',
            'helpdesk.livechat.automation.view' => 'engagement.automation.view',
            'helpdesk.livechat.automation.create' => 'engagement.automation.create',
            'helpdesk.livechat.automation.update' => 'engagement.automation.update',
            'helpdesk.livechat.automation.delete' => 'engagement.automation.delete',
            'helpdesk.livechat.goals.view' => 'engagement.goals.view',
            'helpdesk.livechat.goals.create' => 'engagement.goals.create',
            'helpdesk.livechat.goals.update' => 'engagement.goals.update',
            'helpdesk.livechat.goals.delete' => 'engagement.goals.delete',
            'helpdesk.livechat.manage' => 'engagement.manage',
        ];

        foreach ($renames as $old => $new) {
            $existing = Permission::query()
                ->where('name', $old)
                ->where('guard_name', 'web')
                ->first();

            if ($existing && ! Permission::query()->where('name', $new)->where('guard_name', 'web')->exists()) {
                $existing->update(['name' => $new]);
            }
        }

        // Ensure all permissions exist (idempotent for fresh installs)
        $permissions = array_values($renames);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
