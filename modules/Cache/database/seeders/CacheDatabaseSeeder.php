<?php

namespace Modules\Cache\Database\Seeders;

use Illuminate\Database\Seeder;

class CacheDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CachePermissionsSeeder::class,
        ]);
    }
}
