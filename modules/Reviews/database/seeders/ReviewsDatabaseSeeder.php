<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;

class ReviewsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReviewsPermissionsSeeder::class);
    }
}
