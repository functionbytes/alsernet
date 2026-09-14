<?php

namespace Modules\HelpdeskEmailActivity\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskEmailActivityPermissionsSeeder::class,
        ]);
    }
}
