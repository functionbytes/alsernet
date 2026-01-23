<?php


use App\Models\Lang;
use Illuminate\Database\Seeder;

class LangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the langs table with all available languages for the system.
     * Languages are used for multi-language support throughout the application.
     *
     * Default languages:
     * - Spanish (es) - default
     * - English (en)
     * - Catalan (ca)
     * - French (fr)
     * - Portuguese (pt)
     */
    public function run(): void
    {
        $languages = [
            [
                'name' => 'Español',
                'iso_code' => 'es',
                'locale' => 'es_ES',
                'country_code' => 'ES',
                'available' => true,
                'default' => true,
                'position' => 1,
            ],
            [
                'name' => 'English',
                'iso_code' => 'en',
                'locale' => 'en_US',
                'country_code' => 'US',
                'available' => true,
                'default' => false,
                'position' => 2,
            ],
            [
                'name' => 'Català',
                'iso_code' => 'ca',
                'locale' => 'ca_ES',
                'country_code' => 'ES',
                'available' => true,
                'default' => false,
                'position' => 3,
            ],
            [
                'name' => 'Français',
                'iso_code' => 'fr',
                'locale' => 'fr_FR',
                'country_code' => 'FR',
                'available' => true,
                'default' => false,
                'position' => 4,
            ],
            [
                'name' => 'Português',
                'iso_code' => 'pt',
                'locale' => 'pt_PT',
                'country_code' => 'PT',
                'available' => true,
                'default' => false,
                'position' => 5,
            ],
        ];

        foreach ($languages as $language) {
            Lang::firstOrCreate(
                ['iso_code' => $language['iso_code']],
                $language
            );
        }

        $this->command->info('✅ Languages seeded successfully');
    }
}
