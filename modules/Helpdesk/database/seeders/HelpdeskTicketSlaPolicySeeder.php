<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\TicketSlaPolicy;
use Modules\Helpdesk\Models\TicketStatus;

class HelpdeskTicketSlaPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the helpdesk_ticket_sla_policies table with Service Level Agreements.
     * SLA policies define response and resolution time requirements for tickets.
     *
     * Policy types:
     * - Standard: Regular support, 24-48h response time
     * - Priority: Higher priority, 4-24h response time
     * - Premium: VIP customers, 1-4h response time
     *
     * Each policy has escalation rules and email notifications.
     *
     * Depends on: HelpdeskTicketStatusSeeder (must run first)
     */
    public function run(): void
    {
        // Get statuses
        $newStatus = TicketStatus::where('key', 'new')->first();
        $openStatus = TicketStatus::where('key', 'open')->first();

        if (! $newStatus || ! $openStatus) {
            $this->command->error('❌ Ticket statuses not found. Run HelpdeskTicketStatusSeeder first!');

            return;
        }

        $policies = [
            [
                'uid' => Str::ulid(),
                'name' => 'SLA Estándar',
                'key' => 'standard_sla',
                'description' => 'Política SLA estándar para tickets regulares',
                'priority' => 'medium',
                'first_response_time_hours' => 24,
                'next_response_time_hours' => 48,
                'resolution_time_hours' => 72,
                'first_response_escalation_hours' => 36,
                'next_response_escalation_hours' => 60,
                'resolution_escalation_hours' => 96,
                'escalate_to_manager' => true,
                'send_escalation_email' => true,
                'applicable_status_ids' => json_encode([$newStatus->id, $openStatus->id]),
                'business_hours_only' => false,
                'is_active' => true,
                'position' => 1,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'SLA Prioritario',
                'key' => 'priority_sla',
                'description' => 'Política SLA para tickets prioritarios',
                'priority' => 'high',
                'first_response_time_hours' => 4,
                'next_response_time_hours' => 12,
                'resolution_time_hours' => 24,
                'first_response_escalation_hours' => 6,
                'next_response_escalation_hours' => 18,
                'resolution_escalation_hours' => 30,
                'escalate_to_manager' => true,
                'send_escalation_email' => true,
                'applicable_status_ids' => json_encode([$newStatus->id, $openStatus->id]),
                'business_hours_only' => true,
                'is_active' => true,
                'position' => 2,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'SLA Premium',
                'key' => 'premium_sla',
                'description' => 'Política SLA para clientes premium y VIP',
                'priority' => 'critical',
                'first_response_time_hours' => 1,
                'next_response_time_hours' => 4,
                'resolution_time_hours' => 8,
                'first_response_escalation_hours' => 2,
                'next_response_escalation_hours' => 6,
                'resolution_escalation_hours' => 10,
                'escalate_to_manager' => true,
                'send_escalation_email' => true,
                'applicable_status_ids' => json_encode([$newStatus->id, $openStatus->id]),
                'business_hours_only' => true,
                'is_active' => true,
                'position' => 3,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'SLA Bajo',
                'key' => 'low_sla',
                'description' => 'Política SLA para tickets de baja prioridad',
                'priority' => 'low',
                'first_response_time_hours' => 48,
                'next_response_time_hours' => 72,
                'resolution_time_hours' => 120,
                'first_response_escalation_hours' => 72,
                'next_response_escalation_hours' => 96,
                'resolution_escalation_hours' => 144,
                'escalate_to_manager' => false,
                'send_escalation_email' => true,
                'applicable_status_ids' => json_encode([$newStatus->id, $openStatus->id]),
                'business_hours_only' => false,
                'is_active' => true,
                'position' => 4,
            ],
        ];

        foreach ($policies as $policy) {
            TicketSlaPolicy::firstOrCreate(
                ['key' => $policy['key']],
                $policy
            );
        }

        $this->command->info('✅ Helpdesk ticket SLA policies seeded successfully');
    }
}
