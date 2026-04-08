<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\TicketStatus;

class HelpdeskTicketStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the helpdesk_ticket_statuses table with all available ticket states.
     * Statuses represent the lifecycle of a support ticket from creation to closure.
     *
     * Standard workflow:
     * New → Open → Waiting for Customer → Resolved → Closed
     *
     * Additional states:
     * - On Hold: Temporarily paused
     * - Escalated: Sent to higher level
     * - Reopened: Customer added new response
     *
     * Color codes use Bootstrap standard colors for UI consistency.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Nuevo',
                'key' => 'new',
                'color' => '#0d6efd', // primary blue
                'icon' => 'fa-duotone fa-plus-circle',
                'is_open' => true,
                'is_closed' => false,
                'position' => 1,
                'description' => 'Ticket recién creado, sin asignar',
            ],
            [
                'name' => 'Abierto',
                'key' => 'open',
                'color' => '#0dcaf0', // info cyan
                'icon' => 'fa-duotone fa-folder-open',
                'is_open' => true,
                'is_closed' => false,
                'position' => 2,
                'description' => 'Ticket asignado y en proceso',
            ],
            [
                'name' => 'Esperando Cliente',
                'key' => 'waiting_customer',
                'color' => '#ffc107', // warning yellow
                'icon' => 'fa-duotone fa-clock',
                'is_open' => true,
                'is_closed' => false,
                'position' => 3,
                'description' => 'Esperando respuesta del cliente',
            ],
            [
                'name' => 'En Espera',
                'key' => 'on_hold',
                'color' => '#6c757d', // secondary gray
                'icon' => 'fa-duotone fa-pause-circle',
                'is_open' => false,
                'is_closed' => false,
                'position' => 4,
                'description' => 'Ticket pausado temporalmente',
            ],
            [
                'name' => 'Escalado',
                'key' => 'escalated',
                'color' => '#fd7e14', // orange
                'icon' => 'fa-duotone fa-arrow-up',
                'is_open' => true,
                'is_closed' => false,
                'position' => 5,
                'description' => 'Escalado a nivel superior',
            ],
            [
                'name' => 'Resuelto',
                'key' => 'resolved',
                'color' => '#198754', // success green
                'icon' => 'fa-duotone fa-check-circle',
                'is_open' => false,
                'is_closed' => false,
                'position' => 6,
                'description' => 'Problema resuelto, esperando confirmación del cliente',
            ],
            [
                'name' => 'Reabierto',
                'key' => 'reopened',
                'color' => '#dc3545', // danger red
                'icon' => 'fa-duotone fa-exclamation-circle',
                'is_open' => true,
                'is_closed' => false,
                'position' => 7,
                'description' => 'Cliente agregó nueva respuesta, ticket reabierto',
            ],
            [
                'name' => 'Cerrado',
                'key' => 'closed',
                'color' => '#495057', // dark gray
                'icon' => 'fa-duotone fa-lock',
                'is_open' => false,
                'is_closed' => true,
                'position' => 8,
                'description' => 'Ticket finalizado y cerrado',
            ],
        ];

        foreach ($statuses as $status) {
            TicketStatus::firstOrCreate(
                ['key' => $status['key']],
                $status
            );
        }

        $this->command->info('✅ Helpdesk ticket statuses seeded successfully');
    }
}
