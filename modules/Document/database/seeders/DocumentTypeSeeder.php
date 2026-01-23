<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Document\Entities\DocumentRequirement;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Entities\DocumentTypeValidationStage;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds document types with their requirements and translations.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'slug' => 'corta',
                'label' => 'Licencia de armas cortas',
                'icon' => 'fa-duotone fas fa-gun',
                'color' => '#dc3545',
                'sort_order' => 1,
                'sla_multiplier' => 2.0,
                'validation_stages' => [
                    ['key' => 'documentation_team', 'order' => 1, 'conditions' => ['is_weapon' => true]],
                    ['key' => 'licenses_team', 'order' => 2, 'conditions' => ['is_weapon' => true]],
                    ['key' => 'accounting_team', 'order' => 3, 'conditions' => ['requires_financing' => true]],
                ],
                'requirements' => [
                    [
                        'key' => 'dni_frontal',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Frontal', 'help_text' => 'Foto clara del frente de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Front', 'help_text' => 'Clear photo of the front of your identification'],
                            ['lang_id' => 3, 'name' => 'Avant de la pièce d\'identité', 'help_text' => 'Photo claire de l\'avant de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Frente do ID', 'help_text' => 'Foto clara do frente da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Front', 'help_text' => 'Klares Foto der Vorderseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'dni_trasera',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Trasera', 'help_text' => 'Foto clara del reverso de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Back', 'help_text' => 'Clear photo of the back of your identification'],
                            ['lang_id' => 3, 'name' => 'Verso de la pièce d\'identité', 'help_text' => 'Photo claire du verso de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Verso do ID', 'help_text' => 'Foto clara do verso da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Rückseite', 'help_text' => 'Klares Foto der Rückseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'license_corta',
                        'is_required' => true,
                        'max_file_size' => 10240,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'Licencia de Arma Corta', 'help_text' => 'Copia válida de tu licencia de armas cortas'],
                            ['lang_id' => 2, 'name' => 'Short Gun License', 'help_text' => 'Valid copy of your short gun license'],
                            ['lang_id' => 3, 'name' => 'Permis Arme Courte', 'help_text' => 'Copie valide de votre permis d\'arme courte'],
                            ['lang_id' => 4, 'name' => 'Licença de Arma Curta', 'help_text' => 'Cópia válida da sua licença de arma curta'],
                            ['lang_id' => 5, 'name' => 'Kurzwaffen-Lizenz', 'help_text' => 'Gültige Kopie deiner Kurzwaffenlizenz'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'rifle',
                'label' => 'Licencia de rifles',
                'icon' => 'fa-duotone fas fa-gun',
                'color' => '#ff6b6b',
                'sort_order' => 2,
                'sla_multiplier' => 2.0,
                'validation_stages' => [
                    ['key' => 'documentation_team', 'order' => 1, 'conditions' => ['is_weapon' => true]],
                    ['key' => 'licenses_team', 'order' => 2, 'conditions' => ['is_weapon' => true]],
                    ['key' => 'accounting_team', 'order' => 3, 'conditions' => ['is_weapon' => true]],
                ],
                'requirements' => [
                    [
                        'key' => 'dni_frontal',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Frontal', 'help_text' => 'Foto clara del frente de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Front', 'help_text' => 'Clear photo of the front of your identification'],
                            ['lang_id' => 3, 'name' => 'Avant de la pièce d\'identité', 'help_text' => 'Photo claire de l\'avant de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Frente do ID', 'help_text' => 'Foto clara do frente da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Front', 'help_text' => 'Klares Foto der Vorderseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'dni_trasera',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Trasera', 'help_text' => 'Foto clara del reverso de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Back', 'help_text' => 'Clear photo of the back of your identification'],
                            ['lang_id' => 3, 'name' => 'Verso de la pièce d\'identité', 'help_text' => 'Photo claire du verso de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Verso do ID', 'help_text' => 'Foto clara do verso da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Rückseite', 'help_text' => 'Klares Foto der Rückseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'license_rifle',
                        'is_required' => true,
                        'max_file_size' => 10240,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'Licencia de Rifles', 'help_text' => 'Copia válida de tu licencia de rifles'],
                            ['lang_id' => 2, 'name' => 'Rifle License', 'help_text' => 'Valid copy of your rifle license'],
                            ['lang_id' => 3, 'name' => 'Permis de Fusil', 'help_text' => 'Copie valide de votre permis de fusil'],
                            ['lang_id' => 4, 'name' => 'Licença de Rifle', 'help_text' => 'Cópia válida da sua licença de rifle'],
                            ['lang_id' => 5, 'name' => 'Gewehr-Lizenz', 'help_text' => 'Gültige Kopie deiner Gewehr-Lizenz'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'escopeta',
                'label' => 'Licencia de escopetas',
                'icon' => 'fa-duotone fas fa-gun',
                'color' => '#fd7e14',
                'sort_order' => 3,
                'sla_multiplier' => 2.0,
                'validation_stages' => [
                    ['key' => 'documentation_team', 'order' => 1, 'conditions' => ['is_weapon' => true]],
                    ['key' => 'licenses_team', 'order' => 2, 'conditions' => ['is_weapon' => true]],
                    ['key' => 'accounting_team', 'order' => 3, 'conditions' => ['requires_financing' => true]],
                ],
                'requirements' => [
                    [
                        'key' => 'dni_frontal',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Frontal', 'help_text' => 'Foto clara del frente de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Front', 'help_text' => 'Clear photo of the front of your identification'],
                            ['lang_id' => 3, 'name' => 'Avant de la pièce d\'identité', 'help_text' => 'Photo claire de l\'avant de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Frente do ID', 'help_text' => 'Foto clara do frente da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Front', 'help_text' => 'Klares Foto der Vorderseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'dni_trasera',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Trasera', 'help_text' => 'Foto clara del reverso de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Back', 'help_text' => 'Clear photo of the back of your identification'],
                            ['lang_id' => 3, 'name' => 'Verso de la pièce d\'identité', 'help_text' => 'Photo claire du verso de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Verso do ID', 'help_text' => 'Foto clara do verso da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Rückseite', 'help_text' => 'Klares Foto der Rückseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'license_escopeta',
                        'is_required' => true,
                        'max_file_size' => 10240,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'Licencia de Escopeta', 'help_text' => 'Copia válida de tu licencia de escopeta'],
                            ['lang_id' => 2, 'name' => 'Shotgun License', 'help_text' => 'Valid copy of your shotgun license'],
                            ['lang_id' => 3, 'name' => 'Permis de Fusil de Chasse', 'help_text' => 'Copie valide de votre permis de fusil'],
                            ['lang_id' => 4, 'name' => 'Licença de Espingarda', 'help_text' => 'Cópia válida da sua licença de espingarda'],
                            ['lang_id' => 5, 'name' => 'Schrotflinte-Lizenz', 'help_text' => 'Gültige Kopie deiner Schrotflinte-Lizenz'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'balines',
                'label' => 'Licencia de balines',
                'icon' => 'fa-duotone fas fa-circle',
                'color' => '#ffc107',
                'sort_order' => 4,
                'sla_multiplier' => 0.5,
                'validation_stages' => [
                    ['key' => 'documentation_team', 'order' => 1, 'conditions' => ['is_dni_only' => true]],
                    ['key' => 'accounting_team', 'order' => 2],
                ],
                'requirements' => [
                    [
                        'key' => 'dni_frontal',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Frontal', 'help_text' => 'Foto clara del frente de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Front', 'help_text' => 'Clear photo of the front of your identification'],
                            ['lang_id' => 3, 'name' => 'Avant de la pièce d\'identité', 'help_text' => 'Photo claire de l\'avant de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Frente do ID', 'help_text' => 'Foto clara do frente da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Front', 'help_text' => 'Klares Foto der Vorderseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'dni_trasera',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Trasera', 'help_text' => 'Foto clara del reverso de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Back', 'help_text' => 'Clear photo of the back of your identification'],
                            ['lang_id' => 3, 'name' => 'Verso de la pièce d\'identité', 'help_text' => 'Photo claire du verso de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Verso do ID', 'help_text' => 'Foto clara do verso da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Rückseite', 'help_text' => 'Klares Foto der Rückseite deines Ausweises'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'dni',
                'label' => 'Solo identificación',
                'icon' => 'fa-duotone fas fa-id-card',
                'color' => '#0dcaf0',
                'sort_order' => 5,
                'sla_multiplier' => 0.5,
                'validation_stages' => [
                    ['key' => 'documentation_team', 'order' => 1, 'conditions' => ['is_dni_only' => true]],
                    ['key' => 'accounting_team', 'order' => 2],
                ],
                'requirements' => [
                    [
                        'key' => 'dni_frontal',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Frontal', 'help_text' => 'Foto clara del frente de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Front', 'help_text' => 'Clear photo of the front of your identification'],
                            ['lang_id' => 3, 'name' => 'Avant de la pièce d\'identité', 'help_text' => 'Photo claire de l\'avant de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Frente do ID', 'help_text' => 'Foto clara do frente da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Front', 'help_text' => 'Klares Foto der Vorderseite deines Ausweises'],
                        ],
                    ],
                    [
                        'key' => 'dni_trasera',
                        'is_required' => true,
                        'max_file_size' => 5120,
                        'translations' => [
                            ['lang_id' => 1, 'name' => 'DNI Trasera', 'help_text' => 'Foto clara del reverso de tu identificación'],
                            ['lang_id' => 2, 'name' => 'ID Back', 'help_text' => 'Clear photo of the back of your identification'],
                            ['lang_id' => 3, 'name' => 'Verso de la pièce d\'identité', 'help_text' => 'Photo claire du verso de votre pièce d\'identité'],
                            ['lang_id' => 4, 'name' => 'Verso do ID', 'help_text' => 'Foto clara do verso da sua identificação'],
                            ['lang_id' => 5, 'name' => 'ID-Rückseite', 'help_text' => 'Klares Foto der Rückseite deines Ausweises'],
                        ],
                    ],
                ],
            ],
        ];

        try {
            DB::beginTransaction();

            foreach ($documentTypes as $typeData) {
                $requirements = $typeData['requirements'] ?? [];
                $validationStages = $typeData['validation_stages'] ?? [];
                unset($typeData['requirements']);
                unset($typeData['validation_stages']);

                $documentType = DocumentType::updateOrCreate(
                    ['slug' => $typeData['slug']],
                    array_merge($typeData, [
                        'uid' => Str::ulid(),
                        'is_active' => true,
                    ])
                );

                // Create or update validation stages
                if (! empty($validationStages)) {
                    // Delete existing stages first
                    $documentType->validationStages()->delete();

                    // Handle both array formats: simple array of strings and array of objects
                    foreach ($validationStages as $index => $stageData) {
                        // If it's a string, convert to array format
                        if (is_string($stageData)) {
                            $stageData = ['key' => $stageData, 'order' => $index + 1];
                        }

                        DocumentTypeValidationStage::create([
                            'document_type_id' => $documentType->id,
                            'key' => $stageData['key'],
                            'order' => $stageData['order'] ?? $index + 1,
                            'is_active' => true,
                            'conditions' => $stageData['conditions'] ?? null,
                        ]);
                    }
                }

                // Create or update requirements with translations
                foreach ($requirements as $index => $reqData) {
                    $translations = $reqData['translations'] ?? [];
                    $translationsData = $reqData;
                    unset($translationsData['translations']);

                    $requirement = DocumentRequirement::updateOrCreate(
                        [
                            'document_type_id' => $documentType->id,
                            'key' => $reqData['key'],
                        ],
                        [
                            'is_required' => $reqData['is_required'] ?? true,
                            'accepts_multiple' => $reqData['accepts_multiple'] ?? false,
                            'max_file_size' => $reqData['max_file_size'] ?? 10240,
                            'allowed_extensions' => $reqData['allowed_extensions'] ?? ['pdf', 'jpg', 'jpeg', 'png'],
                            'sort_order' => $index,
                        ]
                    );

                    // Add translations
                    if (! empty($translations)) {
                        foreach ($translations as $translation) {
                            DB::table('document_type_requirement_langs')->updateOrInsert(
                                [
                                    'document_requirement_id' => $requirement->id,
                                    'lang_id' => $translation['lang_id'],
                                ],
                                [
                                    'uid' => (string) Str::uuid(),
                                    'name' => $translation['name'],
                                    'help_text' => $translation['help_text'] ?? '',
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();
            $this->command->info('✅ Document types seeded successfully with multilingual translations');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error seeding document types: '.$e->getMessage());
        }
    }
}
