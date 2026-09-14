<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Document\Entities\DocumentValidatorGroup;

class DocumentValidatorGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'uid' => Str::ulid(),
                'name' => 'Equipo documentación',
                'key' => 'documentation_team',
                'description' => 'Responsables de la validación inicial de documentos',
                'assignment_mode' => 'round_robin',
                'is_default' => true,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Equipo licencias',
                'key' => 'licenses_team',
                'description' => 'Responsables de la validación de licencias y permisos',
                'assignment_mode' => 'load_balanced',
                'is_default' => false,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Equipo contabilidad',
                'key' => 'accounting_team',
                'description' => 'Responsables de la aprobación final y registros contables',
                'assignment_mode' => 'round_robin',
                'is_default' => false,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Equipo legal',
                'key' => 'legal_team',
                'description' => 'Validación de aspectos legales y términos',
                'assignment_mode' => 'round_robin',
                'is_default' => false,
                'sort_order' => 4,
                'is_active' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Equipo callcenter',
                'key' => 'callcenter_team',
                'description' => 'Equipo de callcenter para soporte y gestión de llamadas',
                'assignment_mode' => 'round_robin',
                'is_default' => false,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($groups as $groupData) {
            DocumentValidatorGroup::updateOrCreate(
                ['key' => $groupData['key']],
                $groupData
            );
        }

        $this->command->info('✅ Document validator groups seeded successfully');
    }
}
