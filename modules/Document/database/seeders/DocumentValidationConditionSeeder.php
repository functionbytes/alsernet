<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentValidationCondition;

class DocumentValidationConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the default validation conditions for document workflows.
     * These map condition keys to arrays of sale_types from the document_product_blockades table.
     */
    public function run(): void
    {
        $conditions = [
            [
                'key' => 'is_weapon',
                'condition_type' => 'sale_type',
                'name' => 'Es un arma',
                'description' => 'Documentos que requieren validación de licencias de armas. Incluye escopetas, rifles y armas cortas.',
                'sale_types' => ['escopeta', 'rifle', 'corta', 'armas'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'is_dni_only',
                'condition_type' => 'sale_type',
                'name' => 'Requiere solo identificación',
                'description' => 'Documentos que solo requieren validación de DNI/identificación, sin necesidad de licencias adicionales.',
                'sale_types' => ['dni','balines'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'key' => 'requires_financing',
                'condition_type' => 'field',
                'name' => 'Requiere financiación',
                'description' => 'Documentos que incluyen financiación y requieren validación contable adicional.',
                'sale_types' => [],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($conditions as $conditionData) {
            DocumentValidationCondition::updateOrCreate(
                ['key' => $conditionData['key']],
                $conditionData
            );
        }

        $this->command->info('✅ Document validation conditions seeded successfully');
    }
}
