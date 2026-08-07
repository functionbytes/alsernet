<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskTickets\Models\TicketCategory;

class HelpdeskTicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the helpdesk_ticket_categories table with predefined ticket categories.
     * Categories help organize tickets by type of issue for better routing and analysis.
     *
     * Default categories:
     * - Technical Support: Software bugs, system issues
     * - Billing Inquiry: Payment, invoice, and subscription questions
     * - Feature Request: New functionality requests
     * - Account Management: User profile, password, access issues
     * - Product Information: Details about products and services
     * - Order Status: Tracking and delivery inquiries
     * - Complaint: Service complaints and issues
     *
     * Depends on: None (independent)
     *
     * NOTA: la versión anterior de este seeder usaba columnas ('uid', 'key',
     * 'is_active', 'position') que no existen en la tabla real
     * (helpdesk_ticket_categories solo tiene name/slug/description/icon/
     * color/order/active) — firstOrCreate(['key' => ...]) lanzaba
     * QueryException "Unknown column 'key'" en cada ejecución, por lo que
     * nunca llegó a sembrar ninguna fila. Se corrige usando las columnas
     * reales; idempotente vía 'slug' (columna única).
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Soporte Técnico',
                'slug' => 'soporte-tecnico',
                'description' => 'Problemas técnicos, bugs del sistema y cuestiones de software',
                'color' => '#0d6efd', // primary blue
                'icon' => 'fa-duotone fa-tools',
                'active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Consulta de Facturación',
                'slug' => 'consulta-facturacion',
                'description' => 'Preguntas sobre pagos, facturas y suscripciones',
                'color' => '#0dcaf0', // info cyan
                'icon' => 'fa-duotone fa-receipt',
                'active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Solicitud de Función',
                'slug' => 'solicitud-funcion',
                'description' => 'Solicitudes de nuevas funcionalidades y mejoras',
                'color' => '#198754', // success green
                'icon' => 'fa-duotone fa-lightbulb',
                'active' => true,
                'order' => 3,
            ],
            [
                'name' => 'Gestión de Cuenta',
                'slug' => 'gestion-cuenta',
                'description' => 'Perfil de usuario, contraseña, problemas de acceso',
                'color' => '#ffc107', // warning yellow
                'icon' => 'fa-duotone fa-user-circle',
                'active' => true,
                'order' => 4,
            ],
            [
                'name' => 'Información de Producto',
                'slug' => 'informacion-producto',
                'description' => 'Detalles y características de productos y servicios',
                'color' => '#0d6efd', // primary blue
                'icon' => 'fa-duotone fa-box',
                'active' => true,
                'order' => 5,
            ],
            [
                'name' => 'Estado del Pedido',
                'slug' => 'estado-pedido',
                'description' => 'Seguimiento y consultas de entrega de pedidos',
                'color' => '#0dcaf0', // info cyan
                'icon' => 'fa-duotone fa-shipping-fast',
                'active' => true,
                'order' => 6,
            ],
            [
                'name' => 'Reclamación',
                'slug' => 'reclamacion',
                'description' => 'Quejas sobre el servicio y problemas experimentados',
                'color' => '#dc3545', // danger red
                'icon' => 'fa-duotone fa-exclamation-triangle',
                'active' => true,
                'order' => 7,
            ],
            [
                'name' => 'Otro',
                'slug' => 'otro',
                'description' => 'Otras consultas que no encajan en las categorías anteriores',
                'color' => '#6c757d', // secondary gray
                'icon' => 'fa-duotone fa-question-circle',
                'active' => true,
                'order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command?->info('✅ Helpdesk ticket categories seeded successfully');
    }
}
