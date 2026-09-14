<?php

namespace Modules\Health\Database\Seeders;

use Illuminate\Database\Seeder;

class HealthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HealthPermissionsSeeder::class,
        ]);
    }
}
