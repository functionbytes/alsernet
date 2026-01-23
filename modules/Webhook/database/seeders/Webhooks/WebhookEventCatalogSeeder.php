<?php

namespace Database\Seeders\Webhooks;

use Illuminate\Database\Seeder;
use Modules\Webhook\Models\WebhookEventCatalog;

class WebhookEventCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the webhook_event_catalog table with all available events
     * that can trigger webhooks throughout the system.
     *
     * Events are organized by domain:
     * - documents.* : Document lifecycle events
     * - returns.* : Return request events
     * - suppliers.* : Supplier and automation events
     * - helpdesk.* : Ticket and conversation events
     * - webhooks.* : Webhook delivery events
     */
    public function run(): void
    {
        $events = [
            // Document Events
            [
                'event' => 'documents.created',
                'domain' => 'documents',
                'description' => 'Documento creado en el sistema',
                'payload_example' => json_encode(['document_id' => 'uuid', 'status' => 'pending']),
                'is_active' => true,
            ],
            [
                'event' => 'documents.status_changed',
                'domain' => 'documents',
                'description' => 'Estado del documento cambió',
                'payload_example' => json_encode(['document_id' => 'uuid', 'old_status' => 'pending', 'new_status' => 'received']),
                'is_active' => true,
            ],
            [
                'event' => 'documents.validation_started',
                'domain' => 'documents',
                'description' => 'Validación del documento iniciada',
                'payload_example' => json_encode(['document_id' => 'uuid', 'validator_group_id' => 'id']),
                'is_active' => true,
            ],
            [
                'event' => 'documents.validation_completed',
                'domain' => 'documents',
                'description' => 'Validación del documento completada',
                'payload_example' => json_encode(['document_id' => 'uuid', 'result' => 'approved']),
                'is_active' => true,
            ],
            [
                'event' => 'documents.sla_breached',
                'domain' => 'documents',
                'description' => 'Se incumplió SLA del documento',
                'payload_example' => json_encode(['document_id' => 'uuid', 'sla_policy_id' => 'id']),
                'is_active' => true,
            ],

            // Return Events
            [
                'event' => 'returns.requested',
                'domain' => 'returns',
                'description' => 'Se solicitó una devolución',
                'payload_example' => json_encode(['return_id' => 'uuid', 'order_id' => 'id', 'status' => 'new']),
                'is_active' => true,
            ],
            [
                'event' => 'returns.status_changed',
                'domain' => 'returns',
                'description' => 'Estado de devolución cambió',
                'payload_example' => json_encode(['return_id' => 'uuid', 'old_status' => 'new', 'new_status' => 'verification']),
                'is_active' => true,
            ],
            [
                'event' => 'returns.approved',
                'domain' => 'returns',
                'description' => 'Devolución aprobada',
                'payload_example' => json_encode(['return_id' => 'uuid', 'approved_amount' => 100.00]),
                'is_active' => true,
            ],
            [
                'event' => 'returns.refunded',
                'domain' => 'returns',
                'description' => 'Reembolso procesado',
                'payload_example' => json_encode(['return_id' => 'uuid', 'refunded_amount' => 100.00, 'method' => 'wallet']),
                'is_active' => true,
            ],
            [
                'event' => 'returns.sla_breached',
                'domain' => 'returns',
                'description' => 'Se incumplió SLA de devolución',
                'payload_example' => json_encode(['return_id' => 'uuid']),
                'is_active' => true,
            ],

            // Supplier Events
            [
                'event' => 'suppliers.automation_triggered',
                'domain' => 'suppliers',
                'description' => 'Automatización de proveedor se ejecutó',
                'payload_example' => json_encode(['supplier_id' => 'uuid', 'workflow_id' => 'id', 'status' => 'completed']),
                'is_active' => true,
            ],
            [
                'event' => 'suppliers.content_generated',
                'domain' => 'suppliers',
                'description' => 'Contenido generado por IA de proveedor',
                'payload_example' => json_encode(['supplier_id' => 'uuid', 'content_type' => 'description']),
                'is_active' => true,
            ],
            [
                'event' => 'suppliers.health_check_failed',
                'domain' => 'suppliers',
                'description' => 'Health check de fuente falló',
                'payload_example' => json_encode(['supplier_id' => 'uuid', 'source_id' => 'id', 'error' => 'Connection timeout']),
                'is_active' => true,
            ],
            [
                'event' => 'suppliers.rate_limit_exceeded',
                'domain' => 'suppliers',
                'description' => 'Límite de tasa de proveedor excedido',
                'payload_example' => json_encode(['supplier_id' => 'uuid', 'requests_made' => 1000, 'limit' => 1000]),
                'is_active' => true,
            ],

            // Helpdesk Events
            [
                'event' => 'helpdesk.ticket_created',
                'domain' => 'helpdesk',
                'description' => 'Ticket creado',
                'payload_example' => json_encode(['ticket_id' => 'uuid', 'customer_id' => 'uuid', 'status' => 'new']),
                'is_active' => true,
            ],
            [
                'event' => 'helpdesk.ticket_status_changed',
                'domain' => 'helpdesk',
                'description' => 'Estado del ticket cambió',
                'payload_example' => json_encode(['ticket_id' => 'uuid', 'old_status' => 'new', 'new_status' => 'open']),
                'is_active' => true,
            ],
            [
                'event' => 'helpdesk.ticket_assigned',
                'domain' => 'helpdesk',
                'description' => 'Ticket asignado a agente',
                'payload_example' => json_encode(['ticket_id' => 'uuid', 'agent_id' => 'uuid']),
                'is_active' => true,
            ],
            [
                'event' => 'helpdesk.ticket_closed',
                'domain' => 'helpdesk',
                'description' => 'Ticket cerrado',
                'payload_example' => json_encode(['ticket_id' => 'uuid', 'resolution' => 'solved']),
                'is_active' => true,
            ],
            [
                'event' => 'helpdesk.conversation_started',
                'domain' => 'helpdesk',
                'description' => 'Nueva conversación iniciada',
                'payload_example' => json_encode(['conversation_id' => 'uuid', 'customer_id' => 'uuid']),
                'is_active' => true,
            ],
            [
                'event' => 'helpdesk.sla_breached',
                'domain' => 'helpdesk',
                'description' => 'Se incumplió SLA del ticket',
                'payload_example' => json_encode(['ticket_id' => 'uuid', 'sla_type' => 'first_response']),
                'is_active' => true,
            ],

            // Webhook Events
            [
                'event' => 'webhooks.delivery_failed',
                'domain' => 'webhooks',
                'description' => 'Entrega de webhook falló',
                'payload_example' => json_encode(['delivery_id' => 'uuid', 'subscription_id' => 'uuid', 'error' => 'Connection refused']),
                'is_active' => true,
            ],
            [
                'event' => 'webhooks.delivery_retried',
                'domain' => 'webhooks',
                'description' => 'Entrega de webhook fue reintentada',
                'payload_example' => json_encode(['delivery_id' => 'uuid', 'attempt' => 2]),
                'is_active' => true,
            ],
            [
                'event' => 'webhooks.subscription_created',
                'domain' => 'webhooks',
                'description' => 'Nueva suscripción a webhook creada',
                'payload_example' => json_encode(['subscription_id' => 'uuid', 'integration_id' => 'uuid', 'event' => 'documents.created']),
                'is_active' => true,
            ],
        ];

        foreach ($events as $event) {
            WebhookEventCatalog::firstOrCreate(
                ['event' => $event['event']],
                $event
            );
        }

        $this->command->info('✅ Webhook event catalog seeded successfully');
    }
}
