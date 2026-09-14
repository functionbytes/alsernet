<?php

namespace Modules\PriceLabels\Database\Seeders;

use Illuminate\Database\Seeder;

class PriceLabelsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PriceLabelsPermissionsSeeder::class,
        ]);
    }
}
