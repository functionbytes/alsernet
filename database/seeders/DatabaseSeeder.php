<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run warehouse seeders
        $this->call([
            WarehouseLocationStyleSeeder::class,
            WarehouseLocationConditionSeeder::class,
            WarehouseExampleSeeder::class,
            WarehouseSeedersV2::class,
            Coruna1LocationsSeeder::class,
        ]);
    }
}
