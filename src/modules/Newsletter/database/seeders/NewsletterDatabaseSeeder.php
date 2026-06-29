<?php

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;

class NewsletterDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NewsletterPermissionsSeeder::class);
    }
}
