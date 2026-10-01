<?php

namespace Modules\HelpdeskAiPrompts\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpdeskAiPromptsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskAiPromptsPermissionsSeeder::class,
            AiPromptLibrarySeeder::class,
            AiActionCatalogSeeder::class,
        ]);
    }
}
