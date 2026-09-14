<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nike = Supplier::where('code', 'NIKE')->first();
        $adidas = Supplier::where('code', 'ADIDAS')->first();
        $puma = Supplier::where('code', 'PUMA')->first();
        $asics = Supplier::where('code', 'ASICS')->first();
        $newBalance = Supplier::where('code', 'NB')->first();

        if (! $nike || ! $adidas || ! $puma || ! $asics || ! $newBalance) {
            $this->command->error('Suppliers not found. Please run SupplierSeeder first.');

            return;
        }

        $sources = [
            // Nike sources
            [
                'supplier_id' => $nike->id,
                'source_type' => Source::SOURCE_TYPE_WEBSITE,
                'label' => 'Nike Official Website (ES)',
                'description' => 'Web oficial de Nike España para scraping de información de productos',
                'trust_level' => Source::TRUST_LEVEL_HIGH,
                'usage_notes' => 'Fuente principal de información técnica y descripciones',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'supplier_id' => $nike->id,
                'source_type' => Source::SOURCE_TYPE_FTP,
                'label' => 'Nike FTP Catalog',
                'description' => 'Catálogo de productos Nike vía FTP con imágenes y especificaciones',
                'trust_level' => Source::TRUST_LEVEL_HIGH,
                'usage_notes' => 'Actualización diaria a las 02:00 AM',
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'supplier_id' => $nike->id,
                'source_type' => Source::SOURCE_TYPE_API,
                'label' => 'Nike Product API',
                'description' => 'API oficial de Nike para datos de productos en tiempo real',
                'trust_level' => Source::TRUST_LEVEL_HIGH,
                'usage_notes' => 'Límite de 100 requests/minuto',
                'priority' => 3,
                'is_active' => true,
            ],

            // Adidas sources
            [
                'supplier_id' => $adidas->id,
                'source_type' => Source::SOURCE_TYPE_WEBSITE,
                'label' => 'Adidas Official Website (ES)',
                'description' => 'Web oficial de Adidas España',
                'trust_level' => Source::TRUST_LEVEL_HIGH,
                'usage_notes' => 'Incluye información detallada de tecnologías y materiales',
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'supplier_id' => $adidas->id,
                'source_type' => Source::SOURCE_TYPE_FILE,
                'label' => 'Adidas Excel Catalog',
                'description' => 'Catálogo en formato Excel subido manualmente',
                'trust_level' => Source::TRUST_LEVEL_MEDIUM,
                'usage_notes' => 'Actualización mensual manual',
                'priority' => 2,
                'is_active' => true,
            ],

            // Puma sources
            [
                'supplier_id' => $puma->id,
                'source_type' => Source::SOURCE_TYPE_FTP,
                'label' => 'Puma FTP CSV Catalog',
                'description' => 'Catálogo de productos Puma en formato CSV vía FTP',
                'trust_level' => Source::TRUST_LEVEL_HIGH,
                'usage_notes' => 'Sincronización semanal los lunes',
                'priority' => 1,
                'is_active' => true,
            ],

            // Asics sources
            [
                'supplier_id' => $asics->id,
                'source_type' => Source::SOURCE_TYPE_API,
                'label' => 'Asics Product API',
                'description' => 'API REST de Asics para información de productos',
                'trust_level' => Source::TRUST_LEVEL_HIGH,
                'usage_notes' => 'Autenticación OAuth2, límite 60 requests/minuto',
                'priority' => 1,
                'is_active' => true,
            ],

            // New Balance sources
            [
                'supplier_id' => $newBalance->id,
                'source_type' => Source::SOURCE_TYPE_WEBSITE,
                'label' => 'New Balance Web Scraping',
                'description' => 'Scraping de la web oficial de New Balance',
                'trust_level' => Source::TRUST_LEVEL_MEDIUM,
                'usage_notes' => 'Solo para inspiración, requiere validación manual',
                'priority' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($sources as $sourceData) {
            Source::updateOrCreate(
                [
                    'supplier_id' => $sourceData['supplier_id'],
                    'label' => $sourceData['label'],
                ],
                $sourceData
            );
        }

        $this->command->info('Created supplier sources for all suppliers');
    }
}
