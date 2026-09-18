<?php

namespace Modules\Activity\Database\Seeders;

use Illuminate\Database\Seeder;

class ActivityDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ActivityPermissionsSeeder::class,
        ]);
    }
}
