<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Engagement\Models\WebChannel;

class WebChannelSeeder extends Seeder
{
    public function run(): void
    {
        if (WebChannel::query()->exists()) {
            return;
        }

        WebChannel::factory()
            ->count(2)
            ->sequence(
                ['name' => 'Web principal', 'domain' => 'https://ejemplo.com'],
                ['name' => 'Web staging', 'domain' => 'https://staging.ejemplo.com', 'is_active' => false],
            )
            ->create();
    }
}
