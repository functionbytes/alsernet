<?php

namespace Modules\Locales\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Locales\Models\Locale;

class LocalesSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            ['code' => 'es', 'name' => 'Spanish',    'native_name' => 'Español',    'flag' => '🇪🇸', 'is_default' => true,  'is_active' => true,  'order' => 1],
            ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português',  'flag' => '🇵🇹', 'is_default' => false, 'is_active' => true,  'order' => 2],
            ['code' => 'en', 'name' => 'English',    'native_name' => 'English',    'flag' => '🇬🇧', 'is_default' => false, 'is_active' => true,  'order' => 3],
            ['code' => 'fr', 'name' => 'French',     'native_name' => 'Français',   'flag' => '🇫🇷', 'is_default' => false, 'is_active' => false, 'order' => 4],
            ['code' => 'de', 'name' => 'German',     'native_name' => 'Deutsch',    'flag' => '🇩🇪', 'is_default' => false, 'is_active' => false, 'order' => 5],
            ['code' => 'it', 'name' => 'Italian',    'native_name' => 'Italiano',   'flag' => '🇮🇹', 'is_default' => false, 'is_active' => false, 'order' => 6],
        ];

        foreach ($locales as $data) {
            Locale::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
