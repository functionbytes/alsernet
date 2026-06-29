<?php

namespace Modules\Analytics\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AnalyticsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'analytics.view.all',
            'analytics.dashboard.view',
            'analytics.dashboard.export',
            'analytics.widgets.view',
            'analytics.widgets.general',
            'analytics.widgets.top_pages',
            'analytics.widgets.top_browsers',
            'analytics.widgets.top_referrers',
            'analytics.widgets.manage',
            'analytics.reports.view',
            'analytics.reports.create',
            'analytics.reports.export',
            'analytics.reports.schedule',
            'analytics.settings.view',
            'analytics.settings.update',
            'analytics.settings.manage',
            'analytics.data.view',
            'analytics.data.export',
            'analytics.data.clear_cache',
            'analytics.manage.all',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }
}
