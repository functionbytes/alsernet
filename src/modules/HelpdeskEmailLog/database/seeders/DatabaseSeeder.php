<?php

namespace Modules\HelpdeskEmailLog\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskEmailLogPermissionsSeeder::class,
        ]);
    }
}
