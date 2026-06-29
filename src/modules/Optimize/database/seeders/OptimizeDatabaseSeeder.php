<?php

namespace Modules\Optimize\Database\Seeders;

use Illuminate\Database\Seeder;

class OptimizeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OptimizePermissionsSeeder::class,
        ]);
    }
}
