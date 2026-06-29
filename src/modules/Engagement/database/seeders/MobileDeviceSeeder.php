<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Engagement\Models\MobileDevice;

class MobileDeviceSeeder extends Seeder
{
    public function run(): void
    {
        if (MobileDevice::query()->exists()) {
            return;
        }

        MobileDevice::factory()
            ->count(3)
            ->sequence(
                ['platform' => 'ios', 'os_version' => '17.0'],
                ['platform' => 'android', 'os_version' => '14.0'],
                ['platform' => 'ios', 'os_version' => '16.5', 'push_enabled' => false],
            )
            ->create();
    }
}
