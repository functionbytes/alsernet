<?php

namespace Modules\Page\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Page\Models\PageCategory;

class PageCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Servicios',  'slug' => 'servicios', 'color' => '#b10100'],
            ['name' => 'Blog',       'slug' => 'blog',      'color' => '#13C672'],
            ['name' => 'Legal',      'slug' => 'legal',     'color' => '#6c757d'],
            ['name' => 'Nosotros',   'slug' => 'nosotros',  'color' => '#FA896B'],
            ['name' => 'Soporte',    'slug' => 'soporte',   'color' => '#FEC90F'],
        ];

        foreach ($categories as $data) {
            PageCategory::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'color' => $data['color']]
            );
        }

        $this->command->info('Page categories seeded: '.count($categories));
    }
}
