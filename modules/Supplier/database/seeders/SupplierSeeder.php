<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['code' => 'NIKE', 'label' => 'Nike', 'description' => 'Nike Inc.', 'available' => true, 'priority' => 1],
            ['code' => 'ADIDAS', 'label' => 'Adidas', 'description' => 'Adidas AG', 'available' => true, 'priority' => 2],
            ['code' => 'PUMA', 'label' => 'Puma', 'description' => 'Puma SE', 'available' => true, 'priority' => 3],
            ['code' => 'ASICS', 'label' => 'Asics', 'description' => 'Asics Corp.', 'available' => true, 'priority' => 4],
            ['code' => 'NB', 'label' => 'New Balance', 'description' => 'New Balance Athletics', 'available' => true, 'priority' => 5],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::updateOrCreate(
                ['code' => $supplierData['code']],
                $supplierData
            );
        }

        $this->command->info('Seeded '.count($suppliers).' suppliers.');
    }
}
