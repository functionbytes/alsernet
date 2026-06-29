<?php

namespace Modules\Page\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Page\Models\PageTag;

class PageTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['destacado', 'novedad', 'popular', 'importante', 'empresa'];

        foreach ($tags as $slug) {
            PageTag::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst($slug)]
            );
        }

        $this->command->info('Page tags seeded: '.count($tags));
    }
}
