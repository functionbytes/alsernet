<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Group;

class HelpdeskGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the helpdesk_groups table with default agent groups/teams.
     * Groups organize agents and can handle different types of support tickets.
     *
     * Default groups:
     * - General Support: General questions and issues
     * - Technical Support: Technical problems and bugs
     * - Billing Support: Payment and billing inquiries
     * - Premium Support: VIP customers (paid support tier)
     *
     * Each group can have multiple agents assigned with different roles.
     */
    public function run(): void
    {
        $groups = [
            [
                'uid' => Str::ulid(),
                'name' => 'Soporte General',
                'key' => 'general_support',
                'description' => 'Equipo de soporte general para preguntas generales y consultas',
                'email' => 'support@alsernet.local',
                'is_active' => true,
                'position' => 1,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Soporte Técnico',
                'key' => 'technical_support',
                'description' => 'Equipo especializado en problemas técnicos y bugs',
                'email' => 'tech@alsernet.local',
                'is_active' => true,
                'position' => 2,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Soporte Facturación',
                'key' => 'billing_support',
                'description' => 'Equipo encargado de consultas de pagos y facturación',
                'email' => 'billing@alsernet.local',
                'is_active' => true,
                'position' => 3,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Soporte Premium',
                'key' => 'premium_support',
                'description' => 'Equipo de soporte prioritario para clientes VIP',
                'email' => 'premium@alsernet.local',
                'is_active' => true,
                'position' => 4,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Devoluciones y Logística',
                'key' => 'returns_logistics',
                'description' => 'Equipo especializado en devoluciones y gestión logística',
                'email' => 'returns@alsernet.local',
                'is_active' => true,
                'position' => 5,
            ],
        ];

        foreach ($groups as $group) {
            Group::firstOrCreate(
                ['key' => $group['key']],
                $group
            );
        }

        $this->command->info('✅ Helpdesk groups seeded successfully');
    }
}
