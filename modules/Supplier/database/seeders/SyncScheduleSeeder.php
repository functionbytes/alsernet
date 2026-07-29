<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Sync\SyncSchedule;

class SyncScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            [
                'sync_type' => 'model',
                'label' => 'Modelos - mañana',
                'hour' => 6,
                'minute' => 0,
                'is_enabled' => true,
            ],
            [
                'sync_type' => 'model',
                'label' => 'Modelos - tarde',
                'hour' => 18,
                'minute' => 0,
                'is_enabled' => true,
            ],
            [
                'sync_type' => 'product',
                'label' => 'Productos - mañana',
                'hour' => 7,
                'minute' => 0,
                'is_enabled' => true,
            ],
        ];

        foreach ($schedules as $data) {
            SyncSchedule::updateOrCreate(
                ['sync_type' => $data['sync_type'], 'hour' => $data['hour'], 'minute' => $data['minute']],
                ['label' => $data['label'], 'is_enabled' => $data['is_enabled']],
            );
        }

        $this->command->info('Sync schedules seeded successfully.');
    }
}
