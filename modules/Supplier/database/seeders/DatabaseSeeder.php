<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SupplierPermissionSeeder::class,
            SupplierRolePermissionsSeeder::class,
            SupplierUserSeeder::class,
            SupplierSeeder::class,
            SupplierAutomationSettingSeeder::class,
            SupplierSourceSeeder::class,
            SupplierPromptSeeder::class,
            SupplierSourceOptionSeeder::class,
            SupplierSourceTemplateSeeder::class,
            AiBudgetSeeder::class,
        ]);
    }
}
