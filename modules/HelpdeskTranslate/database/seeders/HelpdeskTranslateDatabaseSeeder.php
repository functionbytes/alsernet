<?php

namespace Modules\HelpdeskTranslate\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpdeskTranslateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            HelpdeskTranslatePermissionsSeeder::class,
        ]);
    }
}
