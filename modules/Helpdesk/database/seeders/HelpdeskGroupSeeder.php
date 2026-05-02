<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpdeskGroupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $groups = [
            [
                'key' => 'general_support',
                'name' => 'Soporte General',
                'description' => 'Equipo de soporte general para preguntas generales y consultas',
                'email' => 'support@alsernet.local',
                'assignment_mode' => 'round_robin',
                'is_active' => 1,
                'default' => 1,
                'position' => 1,
            ],
            [
                'key' => 'technical_support',
                'name' => 'Soporte Técnico',
                'description' => 'Equipo especializado en problemas técnicos y bugs',
                'email' => 'tech@alsernet.local',
                'assignment_mode' => 'round_robin',
                'is_active' => 1,
                'default' => 0,
                'position' => 2,
            ],
            [
                'key' => 'billing_support',
                'name' => 'Soporte Facturación',
                'description' => 'Equipo encargado de consultas de pagos y facturación',
                'email' => 'billing@alsernet.local',
                'assignment_mode' => 'load_balanced',
                'is_active' => 1,
                'default' => 0,
                'position' => 3,
            ],
            [
                'key' => 'premium_support',
                'name' => 'Soporte Premium',
                'description' => 'Equipo de soporte prioritario para clientes VIP',
                'email' => 'premium@alsernet.local',
                'assignment_mode' => 'load_balanced',
                'is_active' => 1,
                'default' => 0,
                'position' => 4,
            ],
            [
                'key' => 'returns_logistics',
                'name' => 'Devoluciones y Logística',
                'description' => 'Equipo especializado en devoluciones y gestión logística',
                'email' => 'returns@alsernet.local',
                'assignment_mode' => 'round_robin',
                'is_active' => 1,
                'default' => 0,
                'position' => 5,
            ],
        ];

        foreach ($groups as $group) {
            DB::connection('helpdesk')->table('helpdesk_groups')->updateOrInsert(
                ['key' => $group['key']],
                array_merge($group, [
                    'uid' => (string) Str::ulid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('✅ Equipos (grupos) creados ('.count($groups).')');
    }
}
