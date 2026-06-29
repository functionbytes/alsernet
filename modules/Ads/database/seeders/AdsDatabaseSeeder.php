<?php

namespace Modules\Ads\Database\Seeders;

use Illuminate\Database\Seeder;

class AdsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdsPermissionsSeeder::class,
        ]);
    }
}
