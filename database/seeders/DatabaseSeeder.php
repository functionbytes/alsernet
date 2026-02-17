<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear roles y permisos primero
        $this->call(\Modules\Role\Database\Seeders\CompleteRolesAndPermissionsSeeder::class);

        // Crear templates
        if (class_exists(\Modules\Template\Database\Seeders\TemplateDatabaseSeeder::class)) {
            $this->call(\Modules\Template\Database\Seeders\TemplateDatabaseSeeder::class);
        }

        // Crear páginas de ejemplo
        if (class_exists(\Modules\Page\Database\Seeders\PageDatabaseSeeder::class)) {
            $this->call(\Modules\Page\Database\Seeders\PageDatabaseSeeder::class);
        }
    }
}
