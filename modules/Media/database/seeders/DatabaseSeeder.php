<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MediaPermissionSeeder::class,
            MediaFolderSeeder::class,
        ]);
    }
}
