<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Document\Entities\DocumentStageEmailAction;

class DocumentStageEmailActionSeeder extends Seeder
{
    public function run(): void
    {
        $this->createStageActions(
            'pending',
            [
                'notify_creator' => true,
                'notify_reviewer' => true,
                'notify_approver' => false,
                'notify_team' => false,
                'send_summary' => false,
            ]
        );

        $this->createStageActions(
            'in_review',
            [
                'notify_creator' => false,
                'notify_reviewer' => true,
                'notify_approver' => false,
                'notify_team' => true,
                'send_summary' => false,
            ]
        );

        $this->createStageActions(
            'approved',
            [
                'notify_creator' => true,
                'notify_reviewer' => false,
                'notify_approver' => true,
                'notify_team' => false,
                'send_summary' => true,
            ]
        );

        $this->createStageActions(
            'rejected',
            [
                'notify_creator' => true,
                'notify_reviewer' => false,
                'notify_approver' => true,
                'notify_team' => true,
                'send_summary' => false,
            ]
        );

        $this->createStageActions(
            'completed',
            [
                'notify_creator' => true,
                'notify_reviewer' => false,
                'notify_approver' => false,
                'notify_team' => false,
                'send_summary' => true,
            ]
        );

        $this->command->info('✅ Document stage email actions seeded successfully');
    }

    /**
     * Create stage email action configurations
     */
    private function createStageActions(string $stage, array $actions): void
    {
        $sortOrder = 0;

        foreach ($actions as $action => $isEnabled) {
            DocumentStageEmailAction::firstOrCreate(
                [
                    'validation_stage' => $stage,
                    'email_action' => $action,
                ],
                [
                    'uid' => Str::uuid()->toString(),
                    'is_enabled' => $isEnabled,
                    'sort_order' => $sortOrder++,
                ]
            );
        }
    }
}
