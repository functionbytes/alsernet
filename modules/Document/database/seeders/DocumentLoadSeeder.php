<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentLoad;

class DocumentLoadSeeder extends Seeder
{
    public function run(): void
    {
        $loads = [
            [
                'key' => 'manual',
                'label' => 'Manual',
                'description' => 'Carga manual de documentos por el administrador',
                'icon' => 'fa-duotone pencil',
                'color' => '#6c757d',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'key' => 'system',
                'label' => 'Sistema',
                'description' => 'Carga automática disparada por eventos o condiciones',
                'icon' => 'fa-duotone zap',
                'color' => '#ffc107',
                'is_active' => true,
                'order' => 2,
            ],
        ];

        foreach ($loads as $load) {
            DocumentLoad::firstOrCreate(
                ['key' => $load['key']],
                $load
            );
        }

        $this->command->info('✅ Document load types seeded successfully');
    }
}
