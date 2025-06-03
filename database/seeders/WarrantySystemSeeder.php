<?php

namespace Database\Seeders;

use App\Models\WarrantyType;
use App\Models\Manufacturer;
use Illuminate\Database\Seeder;

class WarrantySystemSeeder extends Seeder
{
    public function run(): void
    {
        // Crear tipos de garantía
        $this->createWarrantyTypes();

        // Crear fabricantes
        $this->createManufacturers();
    }

    protected function createWarrantyTypes(): void
    {
        $warrantyTypes = [
            [
                'name' => 'Garantía del Fabricante',
                'code' => 'MANUFACTURER',
                'description' => 'Garantía estándar proporcionada por el fabricante del producto',
                'default_duration_months' => 12,
                'max_duration_months' => 24,
                'coverage_details' => [
                    'description' => 'Cubre defectos de fabricación y materiales',
                    'issues' => ['defect', 'manufacturing_error', 'material_failure'],
                ],
                'exclusions' => [
                    'issues' => ['user_damage', 'water_damage', 'normal_wear'],
                ],
                'cost_percentage' => 0,
                'fixed_cost' => 0,
                'transferable' => true,
                'is_active' => true,
                'priority' => 100,
            ],
            [
                'name' => 'Garantía Extendida',
                'code' => 'EXTENDED',
                'description' => 'Extensión de garantía adicional con cobertura ampliada',
                'default_duration_months' => 12,
                'max_duration_months' => 60,
                'coverage_details' => [
                    'description' => 'Cobertura ampliada que incluye defectos y algunos daños accidentales',
                    'issues' => ['defect', 'manufacturing_error', 'material_failure', 'electrical_failure', 'mechanical_failure'],
                ],
                'exclusions' => [
                    'issues' => ['intentional_damage', 'theft', 'loss'],
                ],
                'cost_percentage' => 15,
                'fixed_cost' => 50,
                'transferable' => true,
                'is_active' => true,
                'priority' => 80,
            ],
            [
                'name' => 'Garantía de Tienda',
                'code' => 'STORE',
                'description' => 'Garantía adicional proporcionada por nuestra tienda',
                'default_duration_months' => 6,
                'max_duration_months' => 12,
                'coverage_details' => [
                    'description' => 'Cobertura básica para defectos evidentes',
                    'issues' => ['defect', 'dead_on_arrival'],
                ],
                'exclusions' => [
                    'issues' => ['user_damage', 'water_damage', 'software_issues'],
                ],
                'cost_percentage' => 5,
                'fixed_cost' => 0,
                'transferable' => false,
                'is_active' => true,
                'priority' => 60,
            ],
            [
                'name' => 'Garantía Premium',
                'code' => 'PREMIUM',
                'description' => 'Garantía premium con cobertura completa y servicio prioritario',
                'default_duration_months' => 24,
                'max_duration_months' => 36,
                'coverage_details' => [
                    'description' => 'Cobertura completa incluyendo daños accidentales y servicio a domicilio',
                    'issues' => ['defect', 'manufacturing_error', 'material_failure', 'accidental_damage', 'liquid_damage'],
                ],
                'exclusions' => [
                    'issues' => ['intentional_damage', 'theft', 'loss', 'cosmetic_damage'],
                ],
                'cost_percentage' => 25,
                'fixed_cost' => 100,
                'transferable' => true,
                'is_active' => true,
                'priority' => 90,
            ],
        ];

        foreach ($warrantyTypes as $type) {
            WarrantyType::create($type);
        }
    }

    protected function createManufacturers(): void
    {
        $manufacturers = [
            [
                'name' => 'Samsung Electronics',
                'code' => 'SAMSUNG',
                'contact_email' => 'warranty@samsung.com',
                'contact_phone' => '+1-800-SAMSUNG',
                'website' => 'https://www.samsung.com',
                'warranty_policies' => [
                    'allowed_types' => ['MANUFACTURER', 'EXTENDED'],
                    'allow_extensions' => true,
                    'handled_issues' => ['defect', 'manufacturing_error', 'material_failure'],
                ],
                'api_endpoint' => 'https://api.samsung.com/warranty/v1',
                'api_key' => 'samsung_api_key_here',
                'api_config' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                ],
                'has_api_integration' => true,
                'auto_warranty_registration' => true,
                'warranty_lookup_url' => 'https://warranty.samsung.com/lookup',
                'default_warranty_months' => 12,
                'support_contact_info' => [
                    'phone' => '+1-800-SAMSUNG',
                    'email' => 'support@samsung.com',
                    'hours' => '24/7',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Apple Inc.',
                'code' => 'APPLE',
                'contact_email' => 'warranty@apple.com',
                'contact_phone' => '+1-800-APL-CARE',
                'website' => 'https://www.apple.com',
                'warranty_policies' => [
                    'allowed_types' => ['MANUFACTURER', 'PREMIUM'],
                    'allow_extensions' => true,
                    'handled_issues' => ['defect', 'manufacturing_error', 'material_failure', 'software'],
                ],
                'api_endpoint' => 'https://api.apple.com/warranty/v2',
                'api_key' => 'apple_api_key_here',
                'api_config' => [
                    'timeout' => 45,
                    'retry_attempts' => 2,
                ],
                'has_api_integration' => true,
                'auto_warranty_registration' => true,
                'warranty_lookup_url' => 'https://checkcoverage.apple.com',
                'default_warranty_months' => 12,
                'support_contact_info' => [
                    'phone' => '+1-800-APL-CARE',
                    'email' => 'support@apple.com',
                    'hours' => 'Mon-Sun 8AM-8PM',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Sony Corporation',
                'code' => 'SONY',
                'contact_email' => 'warranty@sony.com',
                'contact_phone' => '+1-800-222-SONY',
                'website' => 'https://www.sony.com',
                'warranty_policies' => [
                    'allowed_types' => ['MANUFACTURER', 'EXTENDED'],
                    'allow_extensions' => true,
                    'handled_issues' => ['defect', 'manufacturing_error', 'material_failure'],
                ],
                'api_endpoint' => null,
                'api_key' => null,
                'has_api_integration' => false,
                'auto_warranty_registration' => false,
                'warranty_lookup_url' => 'https://warranty.sony.com',
                'default_warranty_months' => 12,
                'support_contact_info' => [
                    'phone' => '+1-800-222-SONY',
                    'email' => 'support@sony.com',
                    'hours' => 'Mon-Fri 9AM-6PM EST',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'LG Electronics',
                'code' => 'LG',
                'contact_email' => 'warranty@lg.com',
                'contact_phone' => '+1-800-243-0000',
                'website' => 'https://www.lg.com',
                'warranty_policies' => [
                    'allowed_types' => ['MANUFACTURER', 'EXTENDED'],
                    'allow_extensions' => true,
                    'handled_issues' => ['defect', 'manufacturing_error', 'material_failure', 'electrical_failure'],
                ],
                'api_endpoint' => 'https://api.lg.com/warranty/v1',
                'api_key' => 'lg_api_key_here',
                'api_config' => [
                    'timeout' => 30,
                    'retry_attempts' => 3,
                ],
                'has_api_integration' => true,
                'auto_warranty_registration' => false,
                'warranty_lookup_url' => 'https://gscs.lge.com/uk/web/warranty-check',
                'default_warranty_months' => 24,
                'support_contact_info' => [
                    'phone' => '+1-800-243-0000',
                    'email' => 'support@lg.com',
                    'hours' => 'Mon-Fri 8AM-10PM EST',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($manufacturers as $manufacturer) {
            Manufacturer::create($manufacturer);
        }
    }
}
