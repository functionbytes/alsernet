<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Entities\DocumentValidatorGroupConfiguration;

class DocumentValidatorGroupConfigurationSeeder extends Seeder
{

    public function run(): void
    {
        // Get validator groups
        $docTeam = DocumentValidatorGroup::where('key', 'documentation_team')->first();
        $licenseTeam = DocumentValidatorGroup::where('key', 'licenses_team')->first();
        $accountingTeam = DocumentValidatorGroup::where('key', 'accounting_team')->first();

        if (! $docTeam || ! $licenseTeam || ! $accountingTeam) {
            $this->command->warn('⚠️ Validator groups not found. Skipping configuration seeding.');

            return;
        }

        $configurations = [
            // Documentation Team Config
            [
                'validator_group_id' => $docTeam->id,
                'key' => 'doc_team_required_approvals',
                'label' => 'Aprobaciones requeridas',
                'description' => 'Número de aprobaciones requeridas',
                'value' => '1',
                'category' => 'approvals',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'validator_group_id' => $docTeam->id,
                'key' => 'doc_team_timeout_days',
                'label' => 'Días de espera',
                'description' => 'Días antes de escalación',
                'value' => '5',
                'category' => 'timeout',
                'order' => 2,
                'is_active' => true,
            ],

            // Licenses Team Config
            [
                'validator_group_id' => $licenseTeam->id,
                'key' => 'license_team_required_approvals',
                'label' => 'Aprobaciones requeridas',
                'description' => 'Número de aprobaciones requeridas',
                'value' => '1',
                'category' => 'approvals',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'validator_group_id' => $licenseTeam->id,
                'key' => 'license_team_timeout_days',
                'label' => 'Días de espera',
                'description' => 'Días antes de escalación',
                'value' => '7',
                'category' => 'timeout',
                'order' => 2,
                'is_active' => true,
            ],

            // Accounting Team Config
            [
                'validator_group_id' => $accountingTeam->id,
                'key' => 'accounting_team_required_approvals',
                'label' => 'Aprobaciones requeridas',
                'description' => 'Número de aprobaciones requeridas',
                'value' => '1',
                'category' => 'approvals',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'validator_group_id' => $accountingTeam->id,
                'key' => 'accounting_team_timeout_days',
                'label' => 'Días de espera',
                'description' => 'Días antes de escalación',
                'value' => '3',
                'category' => 'timeout',
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($configurations as $config) {
            DocumentValidatorGroupConfiguration::updateOrCreate(
                [
                    'validator_group_id' => $config['validator_group_id'],
                    'key' => $config['key'],
                ],
                $config
            );
        }

        $this->command->info('✅ Document validator group configurations seeded successfully');
    }
}
