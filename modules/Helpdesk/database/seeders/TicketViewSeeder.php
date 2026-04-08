<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\TicketView;

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
                'icon' => 'fa-duotone ti ti-ticket',
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
                'icon' => 'fa-duotone ti ti-user',
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
                'icon' => 'fa-duotone ti ti-inbox',
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
                'icon' => 'fa-duotone ti ti-user-question',
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
                'icon' => 'fa-duotone ti ti-alert-triangle',
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
                'icon' => 'fa-duotone ti ti-clock-exclamation',
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
                'icon' => 'fa-duotone ti ti-clock-hour-4',
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
                'icon' => 'fa-duotone ti ti-hourglass',
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
                'icon' => 'fa-duotone ti ti-circle-check',
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
                'icon' => 'fa-duotone ti ti-circle-x',
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
                'icon' => 'fa-duotone ti ti-ban',
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
