<?php

namespace Modules\Theme\Database\Seeders;

use Illuminate\Database\Seeder;

class ThemeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ThemePermissionsSeeder::class,
        ]);
    }
}
