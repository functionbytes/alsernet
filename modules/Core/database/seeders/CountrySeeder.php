<?php


use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the countries table with countries where Alsernet operates.
     * Includes main markets: Spain, France, Portugal, and Andorra.
     *
     * This data is used for:
     * - Return address selection
     * - Supplier locations
     * - Customer origin information
     * - Warehouse locations
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'España',
                'iso_code' => 'ES',
                'iso_code_3' => 'ESP',
                'numeric_code' => 724,
                'active' => true,
                'position' => 1,
            ],
            [
                'name' => 'Francia',
                'iso_code' => 'FR',
                'iso_code_3' => 'FRA',
                'numeric_code' => 250,
                'active' => true,
                'position' => 2,
            ],
            [
                'name' => 'Portugal',
                'iso_code' => 'PT',
                'iso_code_3' => 'PRT',
                'numeric_code' => 620,
                'active' => true,
                'position' => 3,
            ],
            [
                'name' => 'Andorra',
                'iso_code' => 'AD',
                'iso_code_3' => 'AND',
                'numeric_code' => 20,
                'active' => true,
                'position' => 4,
            ],
            [
                'name' => 'Italia',
                'iso_code' => 'IT',
                'iso_code_3' => 'ITA',
                'numeric_code' => 380,
                'active' => true,
                'position' => 5,
            ],
            [
                'name' => 'Alemania',
                'iso_code' => 'DE',
                'iso_code_3' => 'DEU',
                'numeric_code' => 276,
                'active' => true,
                'position' => 6,
            ],
            [
                'name' => 'Bélgica',
                'iso_code' => 'BE',
                'iso_code_3' => 'BEL',
                'numeric_code' => 56,
                'active' => true,
                'position' => 7,
            ],
            [
                'name' => 'Países Bajos',
                'iso_code' => 'NL',
                'iso_code_3' => 'NLD',
                'numeric_code' => 528,
                'active' => true,
                'position' => 8,
            ],
            [
                'name' => 'Reino Unido',
                'iso_code' => 'GB',
                'iso_code_3' => 'GBR',
                'numeric_code' => 826,
                'active' => true,
                'position' => 9,
            ],
            [
                'name' => 'Luxemburgo',
                'iso_code' => 'LU',
                'iso_code_3' => 'LUX',
                'numeric_code' => 442,
                'active' => false,
                'position' => 10,
            ],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['iso_code' => $country['iso_code']],
                $country
            );
        }

        $this->command->info('✅ Countries seeded successfully');
    }
}
