<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EngagementPermissionsSeeder::class,
            SegmentSeeder::class,
            AbTestSeeder::class,
            EmailCampaignSeeder::class,
            WebChannelSeeder::class,
            MobileDeviceSeeder::class,
        ]);
    }
}
