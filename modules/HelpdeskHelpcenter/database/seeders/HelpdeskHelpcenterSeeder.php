<?php

namespace Modules\HelpdeskHelpcenter\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpdeskHelpcenterSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskHelpcenterPermissionsSeeder::class,
            HelpCenterSeeder::class,
        ]);
    }
}
