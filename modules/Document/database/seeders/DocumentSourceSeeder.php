<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentSource;

class DocumentSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the document_sources table with all available document sources.
     * Sources represent the channel/origin of where a document came from.
     */
    public function run(): void
    {
        $sources = [
            [
                'key' => 'email',
                'label' => 'Email',
                'description' => 'Cliente envió el documento por email',
                'icon' => 'fa-duotone envelope',
                'color' => '#0d6efd',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'key' => 'whatsapp',
                'label' => 'WhatsApp',
                'description' => 'Cliente envió el documento por whatsApp',
                'icon' => 'fa-duotone phone',
                'color' => '#25d366',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'key' => 'prestashop',
                'label' => 'Prestashop',
                'description' => 'Cliente cargó el documento desde el portal prestashop',
                'icon' => 'fa-duotone globe',
                'color' => '#24b9a6',
                'is_active' => true,
                'order' => 4,
            ],
        ];

        foreach ($sources as $source) {
            DocumentSource::firstOrCreate(
                ['key' => $source['key']],
                $source
            );
        }

        $this->command->info('✅ Document sources seeded successfully');
    }
}
