<?php

namespace Modules\Faqs\Database\Seeders;

use Illuminate\Database\Seeder;

class FaqsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FaqsPermissionsSeeder::class,
        ]);
    }
}
