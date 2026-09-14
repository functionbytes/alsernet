<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentSync;

class DocumentSyncSeeder extends Seeder
{
    public function run(): void
    {
        $syncs = [
            [
                'key' => 'none',
                'label' => 'Sin Sincronización',
                'description' => 'No se sincroniza con sistemas externos',
                'icon' => 'fa-duotone slash',
                'color' => '#6c757d',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'key' => 'prestashop',
                'label' => 'Prestashop',
                'description' => 'Sincronización automática con prestaShop',
                'icon' => 'fa-duotone globe',
                'color' => '#24b9a6',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'key' => 'erp',
                'label' => 'Gestion',
                'description' => 'Sincronización con sistema gestion',
                'icon' => 'fa-duotone database',
                'color' => '#0d6efd',
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($syncs as $sync) {
            DocumentSync::firstOrCreate(
                ['key' => $sync['key']],
                $sync
            );
        }

        $this->command->info('✅ Document sync types seeded successfully');
    }
}
