<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MailVariableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the mail_variables table with dynamic variables that can be used in email templates.
     * These variables are replaced at runtime with actual values from documents, orders, users, etc.
     *
     * Variable categories:
     * - Document variables: Document status, dates, amounts
     * - Customer variables: Name, email, phone, address
     * - Order variables: Order number, items, total
     * - System variables: Company info, support email, links
     *
     * Depends on: LangSeeder (for creating translations)
     */
    public function run(): void
    {
        $variables = [
            // Document Variables
            [
                'uid' => Str::ulid(),
                'name' => 'document.id',
                'label_es' => 'ID del Documento',
                'label_en' => 'Document ID',
                'description' => 'Identificador único del documento',
                'example_value' => 'DOC-2025-001234',
                'category' => 'document',
                'is_system' => true,
                'is_required' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'document.status',
                'label_es' => 'Estado del Documento',
                'label_en' => 'Document Status',
                'description' => 'Estado actual del documento en el workflow',
                'example_value' => 'Aprobado',
                'category' => 'document',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'document.created_at',
                'label_es' => 'Fecha de Creación',
                'label_en' => 'Created Date',
                'description' => 'Fecha en que se creó el documento',
                'example_value' => '2025-12-29',
                'category' => 'document',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'document.due_date',
                'label_es' => 'Fecha de Vencimiento',
                'label_en' => 'Due Date',
                'description' => 'Fecha límite para acciones requeridas',
                'example_value' => '2026-01-29',
                'category' => 'document',
                'is_system' => true,
                'is_required' => false,
            ],

            // Customer Variables
            [
                'uid' => Str::ulid(),
                'name' => 'customer.first_name',
                'label_es' => 'Nombre del Cliente',
                'label_en' => 'Customer First Name',
                'description' => 'Nombre del cliente',
                'example_value' => 'Juan',
                'category' => 'customer',
                'is_system' => true,
                'is_required' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'customer.last_name',
                'label_es' => 'Apellido del Cliente',
                'label_en' => 'Customer Last Name',
                'description' => 'Apellido del cliente',
                'example_value' => 'García',
                'category' => 'customer',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'customer.email',
                'label_es' => 'Email del Cliente',
                'label_en' => 'Customer Email',
                'description' => 'Dirección de correo electrónico del cliente',
                'example_value' => 'juan@example.com',
                'category' => 'customer',
                'is_system' => true,
                'is_required' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'customer.phone',
                'label_es' => 'Teléfono del Cliente',
                'label_en' => 'Customer Phone',
                'description' => 'Número de teléfono del cliente',
                'example_value' => '+34 666 123 456',
                'category' => 'customer',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'customer.company',
                'label_es' => 'Empresa del Cliente',
                'label_en' => 'Customer Company',
                'description' => 'Nombre de la empresa del cliente',
                'example_value' => 'Tech Solutions SL',
                'category' => 'customer',
                'is_system' => true,
                'is_required' => false,
            ],

            // Order Variables
            [
                'uid' => Str::ulid(),
                'name' => 'order.number',
                'label_es' => 'Número de Pedido',
                'label_en' => 'Order Number',
                'description' => 'Número de referencia del pedido',
                'example_value' => 'ORD-2025-5678',
                'category' => 'order',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'order.total',
                'label_es' => 'Total del Pedido',
                'label_en' => 'Order Total',
                'description' => 'Importe total del pedido',
                'example_value' => '€ 299,99',
                'category' => 'order',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'order.created_date',
                'label_es' => 'Fecha del Pedido',
                'label_en' => 'Order Date',
                'description' => 'Fecha en que se realizó el pedido',
                'example_value' => '2025-12-20',
                'category' => 'order',
                'is_system' => true,
                'is_required' => false,
            ],

            // System Variables
            [
                'uid' => Str::ulid(),
                'name' => 'system.company_name',
                'label_es' => 'Nombre de la Empresa',
                'label_en' => 'Company Name',
                'description' => 'Nombre de la empresa remitente',
                'example_value' => 'Alsernet',
                'category' => 'system',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'system.support_email',
                'label_es' => 'Email de Soporte',
                'label_en' => 'Support Email',
                'description' => 'Email de contacto para soporte técnico',
                'example_value' => 'support@alsernet.com',
                'category' => 'system',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'system.support_phone',
                'label_es' => 'Teléfono de Soporte',
                'label_en' => 'Support Phone',
                'description' => 'Número de teléfono de soporte',
                'example_value' => '+34 900 123 456',
                'category' => 'system',
                'is_system' => true,
                'is_required' => false,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'system.website_url',
                'label_es' => 'URL del Sitio Web',
                'label_en' => 'Website URL',
                'description' => 'URL del sitio web principal',
                'example_value' => 'https://www.alsernet.com',
                'category' => 'system',
                'is_system' => true,
                'is_required' => false,
            ],
        ];

        foreach ($variables as $variable) {
            MailVariable::firstOrCreate(
                ['name' => $variable['name']],
                $variable
            );
        }

        $this->command->info('✅ Mail variables seeded successfully');
    }
}
