<?php

namespace Modules\Analytics\Database\Seeders;

use Illuminate\Database\Seeder;

class AnalyticsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AnalyticsPermissionSeeder::class);
    }
}
