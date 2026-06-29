<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentConfiguration;

class DocumentConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            'corta' => [
                'label' => 'Armas cortas',
                'documents' => [
                    'doc_1' => 'DNI - Cara delantera',
                    'doc_2' => 'DNI - Cara trasera',
                    'doc_3' => 'Licencia de armas cortas (tipo B) o licencia de tiro olímpico (tipo F)',
                ],
            ],
            'rifle' => [
                'label' => 'Rifles',
                'documents' => [
                    'doc_1' => 'DNI - Cara delantera',
                    'doc_2' => 'DNI - Cara trasera',
                    'doc_3' => 'Licencia de armas largas rayadas (tipo D)',
                ],
            ],
            'escopeta' => [
                'label' => 'Escopetas',
                'documents' => [
                    'doc_1' => 'DNI - Cara delantera',
                    'doc_2' => 'DNI - Cara trasera',
                    'doc_3' => 'Licencia de escopeta (tipo E)',
                ],
            ],
            'dni' => [
                'label' => 'Solo identificación',
                'documents' => [
                    'doc_1' => 'DNI - Cara delantera',
                    'doc_2' => 'DNI - Cara trasera',
                ],
            ],
        ];

        foreach ($configurations as $documentType => $config) {
            DocumentConfiguration::updateOrCreate(
                ['document_type' => $documentType],
                [
                    'document_type_label' => $config['label'],
                    'required_documents' => $config['documents'],
                ]
            );
        }

        $this->command->info('✅ Document configurations seeded successfully');
    }
}
