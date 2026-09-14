<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;

class SystemDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemPermissionsSeeder::class,
        ]);
    }
}
