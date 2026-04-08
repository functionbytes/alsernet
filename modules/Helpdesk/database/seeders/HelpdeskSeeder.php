<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PrioritiesSeeder::class,
            StatusesSeeder::class,
            CategoriesSeeder::class,
            PermissionsSeeder::class,
        ]);
    }
}
