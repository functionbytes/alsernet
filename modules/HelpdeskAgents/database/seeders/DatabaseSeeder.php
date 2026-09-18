<?php

namespace Modules\HelpdeskAgents\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HelpdeskAgentsPermissionsSeeder::class,
            DefaultAiAgentSeeder::class,
        ]);
    }
}
