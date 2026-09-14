<?php

namespace Modules\HelpdeskAgents\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskAgents\Models\AiAgent;

class DefaultAiAgentSeeder extends Seeder
{
    public function run(): void
    {
        AiAgent::firstOrCreate(
            ['name' => 'Default Agent'],
            [
                'description' => 'Agente IA por defecto del sistema',
                'status' => 'active',
                'type' => 'chat',
                'active' => 1,
            ]
        );
    }
}
