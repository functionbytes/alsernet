<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\Banner;
use Modules\Helpdesk\Models\Brand;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Campaigns\DripCampaign;
use Modules\Helpdesk\Models\Campaigns\DripStep;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\CustomField;
use Modules\Helpdesk\Models\StatusComponent;
use Modules\Helpdesk\Models\StatusIncident;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Models\Workflow;

/**
 * Datos de ejemplo para pantallas de configuracion de Helpdesk que aun
 * estaban vacias (banners, marcas, empresas, campos personalizados,
 * webhooks, workflows, pagina de estado y campanas). Todo idempotente
 * via updateOrCreate/firstOrCreate por clave natural.
 */
class HelpdeskSettingsSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBanners();
        $this->seedBrands();
        $this->seedCompanies();
        $this->seedCustomFields();
        $this->seedWebhooks();
        $this->seedWorkflows();
        $this->seedStatusPage();
        $this->seedBroadcasts();
        $this->seedDripCampaigns();
    }

    private function seedBanners(): void
    {
        $banners = [
            [
                'title' => 'Nueva funcion: respuestas sugeridas con IA',
                'body' => 'Ya puedes activar las sugerencias de respuesta automatica en el panel de conversaciones. Pruebalo desde tu perfil de agente.',
                'type' => 'info',
                'cta_text' => 'Ver novedades',
                'cta_url' => '/panel/helpdesk',
                'is_active' => true,
                'dismissible' => true,
            ],
            [
                'title' => 'Mantenimiento programado este fin de semana',
                'body' => 'El sabado de 02:00 a 04:00 (hora de Madrid) realizaremos tareas de mantenimiento. El servicio podria presentar intermitencias breves.',
                'type' => 'warning',
                'cta_text' => 'Ver estado del servicio',
                'cta_url' => '/status',
                'starts_at' => now()->addDays(2),
                'ends_at' => now()->addDays(4),
                'is_active' => true,
                'dismissible' => true,
            ],
            [
                'title' => 'Recuerda completar tu horario de disponibilidad',
                'body' => 'Los agentes sin horario configurado no reciben asignaciones automaticas fuera de su turno por defecto. Revisa Ajustes > Horarios.',
                'type' => 'success',
                'cta_text' => 'Configurar horario',
                'cta_url' => '/panel/settings/helpdesk/schedule',
                'is_active' => true,
                'dismissible' => true,
            ],
            [
                'title' => 'Incidente de envio de correo resuelto',
                'body' => 'El retraso en el envio de notificaciones por email del pasado 20 de agosto ya fue corregido. Gracias por la paciencia.',
                'type' => 'danger',
                'is_active' => false,
                'dismissible' => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(['title' => $banner['title']], $banner);
        }

        $this->command?->info('Banners creados ('.count($banners).')');
    }

    private function seedBrands(): void
    {
        $brands = [
            [
                'name' => 'Alsernet Tienda',
                'slug' => 'alsernet-tienda',
                'domain' => 'tienda.alsernet.test',
                'primary_color' => '#90bb13',
                'email_from_name' => 'Alsernet Tienda',
                'email_from_address' => 'soporte@alsernet.test',
                'is_active' => true,
            ],
            [
                'name' => 'Alsernet Outlet',
                'slug' => 'alsernet-outlet',
                'domain' => 'outlet.alsernet.test',
                'primary_color' => '#4f6b0a',
                'email_from_name' => 'Alsernet Outlet',
                'email_from_address' => 'outlet@alsernet.test',
                'is_active' => true,
            ],
            [
                'name' => 'Marca Blanca Partner',
                'slug' => 'marca-blanca-partner',
                'domain' => 'soporte.partner-demo.test',
                'primary_color' => '#3a3a3a',
                'email_from_name' => 'Soporte Partner',
                'email_from_address' => 'soporte@partner-demo.test',
                'is_active' => false,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(['slug' => $brand['slug']], $brand);
        }

        $this->command?->info('Marcas creadas ('.count($brands).')');
    }

    private function seedCompanies(): void
    {
        $companies = [
            [
                'name' => 'Distribuciones Iberica S.L.',
                'domain' => 'distribuciones-iberica.test',
                'industry' => 'retail',
                'size' => '51-200',
                'website' => 'https://distribuciones-iberica.test',
                'notes' => 'Cliente mayorista con pedidos recurrentes mensuales. Contacto habitual: departamento de compras.',
                'health_score' => 82,
                'total_revenue' => 145000.00,
            ],
            [
                'name' => 'TechRetail Solutions',
                'domain' => 'techretail-solutions.test',
                'industry' => 'tech',
                'size' => '11-50',
                'website' => 'https://techretail-solutions.test',
                'notes' => 'Integra nuestra API para sincronizar inventario. Requiere soporte tecnico especializado.',
                'health_score' => 91,
                'total_revenue' => 68000.00,
            ],
            [
                'name' => 'Manufacturas del Norte',
                'domain' => 'manufacturas-norte.test',
                'industry' => 'manufacturing',
                'size' => '201-1000',
                'website' => 'https://manufacturas-norte.test',
                'notes' => 'Cuenta grande, varios usuarios con acceso al portal de clientes.',
                'health_score' => 65,
                'total_revenue' => 320000.00,
            ],
            [
                'name' => 'Clinica Dental Sonrisas',
                'domain' => 'clinica-sonrisas.test',
                'industry' => 'health',
                'size' => '1-10',
                'website' => 'https://clinica-sonrisas.test',
                'notes' => 'Pedidos pequenos pero frecuentes de material clinico.',
                'health_score' => 74,
                'total_revenue' => 12500.00,
            ],
            [
                'name' => 'Academia Formacion Plus',
                'domain' => 'formacion-plus.test',
                'industry' => 'education',
                'size' => '11-50',
                'website' => 'https://formacion-plus.test',
                'notes' => 'Solicitan facturacion trimestral consolidada.',
                'health_score' => 58,
                'total_revenue' => 21000.00,
            ],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(['domain' => $company['domain']], $company);
        }

        $this->command?->info('Empresas creadas ('.count($companies).')');
    }

    private function seedCustomFields(): void
    {
        $fields = [
            [
                'entity_type' => 'customer',
                'key' => 'erp_customer_number',
                'label' => 'Numero de cliente ERP',
                'type' => 'text',
                'is_required' => false,
                'order' => 1,
            ],
            [
                'entity_type' => 'customer',
                'key' => 'signup_date',
                'label' => 'Fecha de alta',
                'type' => 'date',
                'is_required' => false,
                'order' => 2,
            ],
            [
                'entity_type' => 'customer',
                'key' => 'account_tier',
                'label' => 'Nivel de cuenta',
                'type' => 'select',
                'options' => ['Estandar', 'Preferente', 'VIP'],
                'default_value' => 'Estandar',
                'is_required' => false,
                'order' => 3,
            ],
            [
                'entity_type' => 'conversation',
                'key' => 'origin_channel_detail',
                'label' => 'Detalle del canal de origen',
                'type' => 'text',
                'is_required' => false,
                'order' => 1,
            ],
            [
                'entity_type' => 'conversation',
                'key' => 'needs_followup',
                'label' => 'Requiere seguimiento',
                'type' => 'boolean',
                'default_value' => '0',
                'is_required' => false,
                'order' => 2,
            ],
            [
                'entity_type' => 'conversation',
                'key' => 'related_order_numbers',
                'label' => 'Numeros de pedido relacionados',
                'type' => 'multi-select',
                'options' => [],
                'is_required' => false,
                'order' => 3,
            ],
        ];

        foreach ($fields as $field) {
            CustomField::updateOrCreate(
                ['entity_type' => $field['entity_type'], 'key' => $field['key']],
                $field
            );
        }

        $this->command?->info('Campos personalizados creados ('.count($fields).')');
    }

    private function seedWebhooks(): void
    {
        $webhooks = [
            [
                'name' => 'Sincronizar tickets resueltos con CRM',
                'url' => 'https://example.com/webhooks/crm-sync',
                'integration_type' => 'generic',
                'events' => ['conversation.resolved', 'conversation.closed'],
                'is_active' => true,
                'user_id' => 1,
                'success_count' => 214,
                'failure_count' => 3,
                'last_triggered_at' => now()->subHours(6),
            ],
            [
                'name' => 'Alertar sistema de monitoreo en conversaciones urgentes',
                'url' => 'https://example.com/webhooks/monitoring-alert',
                'integration_type' => 'generic',
                'events' => ['conversation.created', 'conversation.assigned'],
                'is_active' => true,
                'user_id' => 1,
                'success_count' => 58,
                'failure_count' => 0,
                'last_triggered_at' => now()->subDays(1),
            ],
            [
                'name' => 'Notificar cambios de estado (en pruebas)',
                'url' => 'https://example.com/webhooks/status-changes-test',
                'integration_type' => 'generic',
                'events' => ['conversation.status_changed'],
                'is_active' => false,
                'user_id' => 1,
                'success_count' => 0,
                'failure_count' => 0,
            ],
        ];

        foreach ($webhooks as $webhook) {
            Webhook::updateOrCreate(['name' => $webhook['name']], $webhook);
        }

        $this->command?->info('Webhooks creados ('.count($webhooks).')');
    }

    private function seedWorkflows(): void
    {
        $workflows = [
            [
                'name' => 'Bienvenida y etiquetado automatico',
                'description' => 'Al crear una conversacion nueva, agrega una etiqueta de bienvenida y envia un primer mensaje automatico.',
                'trigger_type' => 'conversation_created',
                'trigger_config' => [],
                'nodes' => [
                    ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'add_tag', 'value' => 'nuevo-contacto'], 'next' => 'n2'],
                    ['id' => 'n2', 'type' => 'action', 'config' => ['action' => 'send_text', 'value' => 'Gracias por escribirnos, en breve un agente te atendera.'], 'next' => 'n3'],
                    ['id' => 'n3', 'type' => 'end', 'config' => [], 'next' => null],
                ],
                'is_active' => true,
                'total_runs' => 312,
                'last_run_at' => now()->subHours(2),
            ],
            [
                'name' => 'Escalado por incumplimiento de SLA',
                'description' => 'Cuando el SLA de una conversacion esta por incumplirse, sube la prioridad y notifica al supervisor.',
                'trigger_type' => 'sla_breach',
                'trigger_config' => [],
                'nodes' => [
                    ['id' => 'n1', 'type' => 'action', 'config' => ['action' => 'set_priority', 'value' => 'high'], 'next' => 'n2'],
                    ['id' => 'n2', 'type' => 'action', 'config' => ['action' => 'assign_user', 'value' => 'supervisor'], 'next' => 'n3'],
                    ['id' => 'n3', 'type' => 'end', 'config' => [], 'next' => null],
                ],
                'is_active' => true,
                'total_runs' => 27,
                'last_run_at' => now()->subDays(3),
            ],
            [
                'name' => 'Encuesta de satisfaccion tras cierre',
                'description' => 'Al cerrar una conversacion, espera 30 minutos y solicita la valoracion CSAT.',
                'trigger_type' => 'conversation_closed',
                'trigger_config' => [],
                'nodes' => [
                    ['id' => 'n1', 'type' => 'wait', 'config' => ['minutes' => 30], 'next' => 'n2'],
                    ['id' => 'n2', 'type' => 'action', 'config' => ['action' => 'send_text', 'value' => 'Gracias por contactarnos. Como valorarias la atencion recibida?'], 'next' => 'n3'],
                    ['id' => 'n3', 'type' => 'end', 'config' => [], 'next' => null],
                ],
                'is_active' => false,
                'total_runs' => 0,
            ],
        ];

        foreach ($workflows as $workflow) {
            Workflow::updateOrCreate(['name' => $workflow['name']], $workflow);
        }

        $this->command?->info('Workflows creados ('.count($workflows).')');
    }

    private function seedStatusPage(): void
    {
        $components = [
            ['name' => 'API', 'description' => 'API publica de integracion', 'status' => 'operational', 'order' => 1, 'is_visible' => true],
            ['name' => 'Panel de agentes', 'description' => 'Aplicacion web para agentes y supervisores', 'status' => 'operational', 'order' => 2, 'is_visible' => true],
            ['name' => 'Envio de correo', 'description' => 'Notificaciones y respuestas por email', 'status' => 'operational', 'order' => 3, 'is_visible' => true],
            ['name' => 'Widget de chat', 'description' => 'Chat en vivo embebido en el sitio', 'status' => 'operational', 'order' => 4, 'is_visible' => true],
            ['name' => 'Integraciones (WhatsApp / Facebook / Instagram)', 'description' => 'Canales sociales conectados', 'status' => 'operational', 'order' => 5, 'is_visible' => true],
        ];

        $componentModels = [];
        foreach ($components as $component) {
            $componentModels[$component['name']] = StatusComponent::updateOrCreate(
                ['name' => $component['name']],
                $component
            );
        }

        $this->command?->info('Componentes de estado creados ('.count($components).')');

        $incidents = [
            [
                'title' => 'Degradacion en el envio de correo',
                'body' => 'Detectamos retrasos de hasta 20 minutos en el envio de notificaciones por correo. El equipo aplico un ajuste de configuracion en el servicio de colas y el envio volvio a la normalidad.',
                'severity' => 'major',
                'status' => 'resolved',
                'affected_components' => [$componentModels['Envio de correo']->id ?? null],
                'started_at' => now()->subDays(11)->setTime(9, 40),
                'resolved_at' => now()->subDays(11)->setTime(11, 5),
            ],
            [
                'title' => 'Mantenimiento programado de base de datos',
                'body' => 'Realizamos una migracion de indices en la base de datos principal. El servicio estuvo en modo de solo lectura durante la ventana de mantenimiento.',
                'severity' => 'minor',
                'status' => 'resolved',
                'affected_components' => [$componentModels['API']->id ?? null, $componentModels['Panel de agentes']->id ?? null],
                'started_at' => now()->subDays(25)->setTime(2, 0),
                'resolved_at' => now()->subDays(25)->setTime(3, 15),
            ],
        ];

        foreach ($incidents as $incident) {
            StatusIncident::updateOrCreate(['title' => $incident['title']], $incident);
        }

        $this->command?->info('Incidentes de estado creados ('.count($incidents).')');
    }

    private function seedBroadcasts(): void
    {
        $broadcasts = [
            [
                'name' => 'Aviso de horario especial - Puente de diciembre',
                'channel' => 'email',
                'template_type' => 'text',
                'body' => 'Te avisamos que durante el puente de diciembre nuestro horario de atencion sera reducido. Consulta los detalles en nuestra pagina de ayuda.',
                'status' => 'sent',
                'sent_at' => now()->subDays(20),
                'recipients_count' => 1840,
                'delivered_count' => 1795,
                'failed_count' => 45,
                'created_by' => 1,
            ],
            [
                'name' => 'Lanzamiento nueva coleccion - WhatsApp VIP',
                'channel' => 'whatsapp',
                'template_type' => 'hsm',
                'template_id' => 'nueva_coleccion_disponible',
                'template_params' => ['nombre_cliente' => '{{1}}'],
                'status' => 'sent',
                'sent_at' => now()->subDays(6),
                'recipients_count' => 320,
                'delivered_count' => 312,
                'failed_count' => 8,
                'created_by' => 1,
            ],
            [
                'name' => 'Encuesta de satisfaccion trimestral',
                'channel' => 'email',
                'template_type' => 'text',
                'body' => 'Nos gustaria conocer tu opinion. Dedica 2 minutos a responder nuestra encuesta trimestral de satisfaccion.',
                'status' => 'draft',
                'recipients_count' => 0,
                'delivered_count' => 0,
                'failed_count' => 0,
                'created_by' => 1,
            ],
        ];

        foreach ($broadcasts as $broadcast) {
            Broadcast::updateOrCreate(['name' => $broadcast['name']], $broadcast);
        }

        $this->command?->info('Broadcasts creados ('.count($broadcasts).')');
    }

    private function seedDripCampaigns(): void
    {
        $campaign = DripCampaign::updateOrCreate(
            ['name' => 'Bienvenida a nuevos clientes'],
            [
                'name' => 'Bienvenida a nuevos clientes',
                'description' => 'Secuencia de mensajes automaticos para acompanar a un cliente nuevo durante su primera semana.',
                'trigger_type' => 'tag_added',
                'trigger_value' => 'nuevo-contacto',
                'is_active' => true,
            ]
        );

        $steps = [
            [
                'step_order' => 1,
                'delay_minutes' => 0,
                'channel' => null,
                'template_type' => 'text',
                'body' => 'Bienvenido/a. Este es tu canal directo con nuestro equipo de soporte para cualquier consulta.',
            ],
            [
                'step_order' => 2,
                'delay_minutes' => 1440,
                'channel' => null,
                'template_type' => 'text',
                'body' => 'Un dia despues: si tienes dudas sobre tu primer pedido o el uso de la plataforma, escribenos por aqui.',
            ],
            [
                'step_order' => 3,
                'delay_minutes' => 10080,
                'channel' => null,
                'template_type' => 'text',
                'body' => 'Una semana despues: como ha sido tu experiencia hasta ahora? Tu opinion nos ayuda a mejorar.',
            ],
        ];

        foreach ($steps as $step) {
            DripStep::updateOrCreate(
                ['campaign_id' => $campaign->id, 'step_order' => $step['step_order']],
                array_merge($step, ['campaign_id' => $campaign->id])
            );
        }

        $secondCampaign = DripCampaign::updateOrCreate(
            ['name' => 'Reactivacion tras cierre sin respuesta'],
            [
                'name' => 'Reactivacion tras cierre sin respuesta',
                'description' => 'Vuelve a contactar a clientes cuya conversacion se cerro sin confirmar si el problema quedo resuelto.',
                'trigger_type' => 'conversation_closed',
                'trigger_value' => null,
                'is_active' => false,
            ]
        );

        DripStep::updateOrCreate(
            ['campaign_id' => $secondCampaign->id, 'step_order' => 1],
            [
                'campaign_id' => $secondCampaign->id,
                'step_order' => 1,
                'delay_minutes' => 4320,
                'channel' => null,
                'template_type' => 'text',
                'body' => 'Hace unos dias cerramos tu conversacion. Todo quedo resuelto? Si necesitas algo mas, responde a este mensaje.',
            ]
        );

        $this->command?->info('Campanas de goteo creadas (2)');
    }
}
