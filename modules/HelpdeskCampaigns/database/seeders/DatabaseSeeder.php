<?php

namespace Modules\HelpdeskCampaigns\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskCampaignsPermissionsSeeder::class,
        ]);
    }
}
