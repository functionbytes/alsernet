<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\TicketView;

class TicketViewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $views = [
            [
                'name' => 'Todos los tickets',
                'slug' => 'all-tickets',
                'description' => 'Vista de todos los tickets del sistema',
                'icon' => 'fas fa-ticket',
                'color' => '#5D87FF',
                'is_system' => true,
                'is_shared' => true,
                'order' => 1,
                'filters' => [],
            ],
            [
                'name' => 'Mis tickets',
                'slug' => 'my-tickets',
                'description' => 'Tickets asignados a mí',
                'icon' => 'fas fa-user',
                'color' => '#49BEFF',
                'is_system' => true,
                'is_shared' => false,
                'order' => 2,
                'filters' => [
                    'assigned_to_me' => true,
                ],
            ],
            [
                'name' => 'Tickets abiertos',
                'slug' => 'open-tickets',
                'description' => 'Todos los tickets activos',
                'icon' => 'fas fa-inbox',
                'color' => '#13DEB9',
                'is_system' => true,
                'is_shared' => true,
                'order' => 3,
                'filters' => [
                    'status_type' => 'open',
                ],
            ],
            [
                'name' => 'Tickets sin asignar',
                'slug' => 'unassigned-tickets',
                'description' => 'Tickets pendientes de asignación',
                'icon' => 'fas fa-user-question',
                'color' => '#FEC90F',
                'is_system' => true,
                'is_shared' => true,
                'order' => 4,
                'filters' => [
                    'assignee_id' => null,
                    'status_type' => 'open',
                ],
            ],
            [
                'name' => 'Prioridad urgente',
                'slug' => 'urgent-tickets',
                'description' => 'Tickets con prioridad urgente',
                'icon' => 'fas fa-triangle-exclamation',
                'color' => '#FA896B',
                'is_system' => true,
                'is_shared' => true,
                'order' => 5,
                'filters' => [
                    'priority' => 'urgent',
                    'status_type' => 'open',
                ],
            ],
            [
                'name' => 'SLA incumplido',
                'slug' => 'sla-breached',
                'description' => 'Tickets con SLA vencido',
                'icon' => 'fas fa-clock',
                'color' => '#FA896B',
                'is_system' => true,
                'is_shared' => true,
                'order' => 6,
                'filters' => [
                    'sla_breach' => true,
                ],
            ],
            [
                'name' => 'SLA próximo a vencer',
                'slug' => 'sla-warning',
                'description' => 'Tickets cerca del límite SLA',
                'icon' => 'fas fa-clock-rotate-left',
                'color' => '#FEC90F',
                'is_system' => true,
                'is_shared' => true,
                'order' => 7,
                'filters' => [
                    'sla_warning' => true,
                ],
            ],
            [
                'name' => 'Esperando cliente',
                'slug' => 'waiting-customer',
                'description' => 'Tickets esperando respuesta del cliente',
                'icon' => 'fas fa-hourglass-half',
                'color' => '#FFAE1F',
                'is_system' => true,
                'is_shared' => true,
                'order' => 8,
                'filters' => [
                    'status_name' => 'Waiting on Customer',
                ],
            ],
            [
                'name' => 'Resueltos',
                'slug' => 'resolved-tickets',
                'description' => 'Tickets marcados como resueltos',
                'icon' => 'fas fa-circle-check',
                'color' => '#13DEB9',
                'is_system' => true,
                'is_shared' => true,
                'order' => 9,
                'filters' => [
                    'status_name' => 'Resolved',
                ],
            ],
            [
                'name' => 'Cerrados',
                'slug' => 'closed-tickets',
                'description' => 'Tickets cerrados',
                'icon' => 'fas fa-circle-xmark',
                'color' => '#539BFF',
                'is_system' => true,
                'is_shared' => true,
                'order' => 10,
                'filters' => [
                    'status_type' => 'closed',
                ],
            ],
            [
                'name' => 'Spam',
                'slug' => 'spam-tickets',
                'description' => 'Tickets marcados como spam',
                'icon' => 'fas fa-ban',
                'color' => '#FA896B',
                'is_system' => true,
                'is_shared' => true,
                'order' => 11,
                'filters' => [
                    'is_spam' => true,
                ],
            ],
        ];

        foreach ($views as $viewData) {
            TicketView::firstOrCreate(
                ['slug' => $viewData['slug']],
                $viewData
            );
        }

        $this->command->info('Created '.count($views).' default ticket views');
    }
}
