<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\TicketCategory;

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
     */
    public function run(): void
    {
        $categories = [
            [
                'uid' => Str::ulid(),
                'name' => 'Soporte Técnico',
                'key' => 'technical_support',
                'description' => 'Problemas técnicos, bugs del sistema y cuestiones de software',
                'color' => '#0d6efd', // primary blue
                'icon' => 'fa-duotone fa-tools',
                'is_active' => true,
                'position' => 1,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Consulta de Facturación',
                'key' => 'billing_inquiry',
                'description' => 'Preguntas sobre pagos, facturas y suscripciones',
                'color' => '#0dcaf0', // info cyan
                'icon' => 'fa-duotone fa-receipt',
                'is_active' => true,
                'position' => 2,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Solicitud de Función',
                'key' => 'feature_request',
                'description' => 'Solicitudes de nuevas funcionalidades y mejoras',
                'color' => '#198754', // success green
                'icon' => 'fa-duotone fa-lightbulb',
                'is_active' => true,
                'position' => 3,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Gestión de Cuenta',
                'key' => 'account_management',
                'description' => 'Perfil de usuario, contraseña, problemas de acceso',
                'color' => '#ffc107', // warning yellow
                'icon' => 'fa-duotone fa-user-circle',
                'is_active' => true,
                'position' => 4,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Información de Producto',
                'key' => 'product_information',
                'description' => 'Detalles y características de productos y servicios',
                'color' => '#0d6efd', // primary blue
                'icon' => 'fa-duotone fa-box',
                'is_active' => true,
                'position' => 5,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Estado del Pedido',
                'key' => 'order_status',
                'description' => 'Seguimiento y consultas de entrega de pedidos',
                'color' => '#0dcaf0', // info cyan
                'icon' => 'fa-duotone fa-shipping-fast',
                'is_active' => true,
                'position' => 6,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Reclamación',
                'key' => 'complaint',
                'description' => 'Quejas sobre el servicio y problemas experienciados',
                'color' => '#dc3545', // danger red
                'icon' => 'fa-duotone fa-exclamation-triangle',
                'is_active' => true,
                'position' => 7,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Otro',
                'key' => 'other',
                'description' => 'Otras consultas que no encajan en las categorías anteriores',
                'color' => '#6c757d', // secondary gray
                'icon' => 'fa-duotone fa-question-circle',
                'is_active' => true,
                'position' => 8,
            ],
        ];

        foreach ($categories as $category) {
            TicketCategory::firstOrCreate(
                ['key' => $category['key']],
                $category
            );
        }

        $this->command->info('✅ Helpdesk ticket categories seeded successfully');
    }
}
