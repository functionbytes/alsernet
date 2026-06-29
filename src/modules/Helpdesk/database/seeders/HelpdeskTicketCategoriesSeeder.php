<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpdeskTicketCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            [
                'slug' => 'soporte-tecnico',
                'name' => 'Soporte técnico',
                'description' => 'Problemas técnicos, errores de software y fallas del sistema',
                'icon' => 'fas fa-wrench',
                'color' => '#0D6EFD',
                'order' => 1,
                'active' => 1,
            ],
            [
                'slug' => 'facturacion',
                'name' => 'Facturación',
                'description' => 'Consultas sobre pagos, facturas, reembolsos y suscripciones',
                'icon' => 'fas fa-file-invoice-dollar',
                'color' => '#198754',
                'order' => 2,
                'active' => 1,
            ],
            [
                'slug' => 'cuenta-acceso',
                'name' => 'Cuenta y acceso',
                'description' => 'Problemas de inicio de sesión, contraseñas y gestión de cuenta',
                'icon' => 'fas fa-user-shield',
                'color' => '#6F42C1',
                'order' => 3,
                'active' => 1,
            ],
            [
                'slug' => 'consulta-general',
                'name' => 'Consulta general',
                'description' => 'Preguntas generales sobre el servicio, planes y características',
                'icon' => 'fas fa-circle-question',
                'color' => '#0DCAF0',
                'order' => 4,
                'active' => 1,
            ],
            [
                'slug' => 'incidencia',
                'name' => 'Incidencia',
                'description' => 'Reportes de incidentes críticos que afectan el servicio',
                'icon' => 'fas fa-triangle-exclamation',
                'color' => '#DC3545',
                'order' => 5,
                'active' => 1,
            ],
            [
                'slug' => 'solicitud-cambio',
                'name' => 'Solicitud de cambio',
                'description' => 'Solicitudes de modificación de servicio, migración o configuración',
                'icon' => 'fas fa-arrows-rotate',
                'color' => '#FD7E14',
                'order' => 6,
                'active' => 1,
            ],
            [
                'slug' => 'onboarding',
                'name' => 'Onboarding',
                'description' => 'Asistencia durante la incorporación de nuevos clientes',
                'icon' => 'fas fa-rocket',
                'color' => '#FFC107',
                'order' => 7,
                'active' => 1,
            ],
        ];

        foreach ($categories as $category) {
            DB::connection('helpdesk')->table('helpdesk_ticket_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('✅ Categorías de tickets creadas ('.count($categories).')');
    }
}
