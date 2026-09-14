<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Entities\DocumentStatusTransition;

class DocumentStatusTransitionSeeder extends Seeder
{
    public function run(): void
    {
        $transitions = [
            // From PENDING
            ['from' => 'pending', 'to' => 'incomplete', 'permission' => null],
            ['from' => 'pending', 'to' => 'awaiting_documents', 'permission' => null],
            ['from' => 'pending', 'to' => 'cancelled', 'permission' => null],

            // From INCOMPLETE
            ['from' => 'incomplete', 'to' => 'awaiting_documents', 'permission' => null],
            ['from' => 'incomplete', 'to' => 'rejected', 'permission' => 'documents.reject'],
            ['from' => 'incomplete', 'to' => 'cancelled', 'permission' => null],

            // From AWAITING_DOCUMENTS
            ['from' => 'awaiting_documents', 'to' => 'approved', 'permission' => 'documents.approve', 'requires_all_documents_uploaded' => true],
            ['from' => 'awaiting_documents', 'to' => 'incomplete', 'permission' => null],
            ['from' => 'awaiting_documents', 'to' => 'cancelled', 'permission' => null],

            // From APPROVED
            ['from' => 'approved', 'to' => 'completed', 'permission' => 'documents.complete'],
            ['from' => 'approved', 'to' => 'rejected', 'permission' => 'documents.reject'],

            // From REJECTED
            ['from' => 'rejected', 'to' => 'awaiting_documents', 'permission' => null],
            ['from' => 'rejected', 'to' => 'cancelled', 'permission' => null],
        ];

        foreach ($transitions as $transition) {
            $fromStatus = DocumentStatus::where('key', $transition['from'])->first();
            $toStatus = DocumentStatus::where('key', $transition['to'])->first();

            if ($fromStatus && $toStatus) {
                DocumentStatusTransition::firstOrCreate(
                    [
                        'from_status_id' => $fromStatus->id,
                        'to_status_id' => $toStatus->id,
                    ],
                    [
                        'permission' => $transition['permission'] ?? null,
                        'requires_all_documents_uploaded' => $transition['requires_all_documents_uploaded'] ?? false,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ Document status transitions seeded successfully');
    }
}
