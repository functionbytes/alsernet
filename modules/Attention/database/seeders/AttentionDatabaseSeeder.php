<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;

class AttentionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeders de configuracion base
        $this->call([
            AttentionTypesSeeder::class,
            AttentionCategoriesSeeder::class,
            AttentionDepartmentsSeeder::class,
            AttentionSedesSeeder::class,
            AttentionSlaPoliciesSeeder::class,
            AttentionPermissionsSeeder::class,
            AttentionEmailVariablesSeeder::class,
            AttentionEmailTemplatesSeeder::class,
        ]);

        // Opcional: Cargar datos de demostración
        if ($this->command->confirm('Do you want to load demo data? (50 sample peticiones with notes and actions)', false)) {
            $this->call([
                AttentionDemoDataSeeder::class,
            ]);
        }
    }
}
