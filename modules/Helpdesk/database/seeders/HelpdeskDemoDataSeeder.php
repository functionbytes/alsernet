<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\AutomationRule;
use Modules\Helpdesk\Models\BusinessHour;
use Modules\Helpdesk\Models\CannedReply;
use Modules\Helpdesk\Models\Macro;
use Modules\Helpdesk\Models\SlaPolicy;

class HelpdeskDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBusinessHours();
        $this->seedSlaPolicies();
        $this->seedAutomationRules();
        $this->seedMacros();
        $this->seedCannedReplies();
    }

    private function seedBusinessHours(): void
    {
        $schedule = [
            0 => ['is_open' => false, 'opens_at' => null,    'closes_at' => null],
            1 => ['is_open' => true,  'opens_at' => '09:00', 'closes_at' => '18:00'],
            2 => ['is_open' => true,  'opens_at' => '09:00', 'closes_at' => '18:00'],
            3 => ['is_open' => true,  'opens_at' => '09:00', 'closes_at' => '18:00'],
            4 => ['is_open' => true,  'opens_at' => '09:00', 'closes_at' => '18:00'],
            5 => ['is_open' => true,  'opens_at' => '09:00', 'closes_at' => '18:00'],
            6 => ['is_open' => true,  'opens_at' => '10:00', 'closes_at' => '14:00'],
        ];

        foreach ($schedule as $day => $data) {
            BusinessHour::updateOrCreate(
                ['day_of_week' => $day],
                array_merge($data, ['timezone' => 'America/Mexico_City'])
            );
        }
    }

    private function seedSlaPolicies(): void
    {
        $policies = [
            [
                'name' => 'SLA Estándar',
                'description' => 'Política base para tickets de soporte general.',
                'first_response_time_hours' => 4,
                'resolution_time_hours' => 24,
                'business_hours_only' => true,
                'warning_threshold_percent' => 80,
                'is_active' => true,
            ],
            [
                'name' => 'SLA Urgente',
                'description' => 'Para incidencias críticas que afectan producción.',
                'first_response_time_hours' => 1,
                'resolution_time_hours' => 4,
                'business_hours_only' => false,
                'warning_threshold_percent' => 75,
                'is_active' => true,
            ],
            [
                'name' => 'SLA Premium',
                'description' => 'Clientes con plan premium con tiempos garantizados.',
                'first_response_time_hours' => 2,
                'resolution_time_hours' => 8,
                'business_hours_only' => true,
                'warning_threshold_percent' => 70,
                'is_active' => true,
            ],
            [
                'name' => 'SLA Bajo (Best Effort)',
                'description' => 'Tickets de baja prioridad sin compromiso de tiempo estricto.',
                'first_response_time_hours' => 24,
                'resolution_time_hours' => 72,
                'business_hours_only' => true,
                'warning_threshold_percent' => 90,
                'is_active' => false,
            ],
        ];

        foreach ($policies as $policy) {
            SlaPolicy::firstOrCreate(
                ['name' => $policy['name']],
                array_merge($policy, ['uid' => Str::uuid()->toString()])
            );
        }
    }

    private function seedAutomationRules(): void
    {
        $rules = [
            [
                'name' => 'Asignar ticket al crear si viene de email',
                'description' => 'Cuando se crea una conversacion por email, asignarla al grupo de Soporte Email.',
                'trigger_event' => 'conversation.created',
                'conditions' => [
                    ['field' => 'channel', 'operator' => 'equals', 'value' => 'email'],
                ],
                'actions' => [
                    ['type' => 'assign_group', 'value' => 'Soporte Email'],
                    ['type' => 'add_tag',       'value' => 'email-entrante'],
                ],
                'is_active' => true,
                'order' => 1,
                'run_count' => 47,
            ],
            [
                'name' => 'Etiquetar conversaciones urgentes',
                'description' => 'Si el asunto contiene "urgente" o "critico", agregar etiqueta y cambiar prioridad.',
                'trigger_event' => 'conversation.created',
                'conditions' => [
                    ['field' => 'subject', 'operator' => 'contains', 'value' => 'urgente'],
                ],
                'actions' => [
                    ['type' => 'add_tag',         'value' => 'urgente'],
                    ['type' => 'change_priority', 'value' => 'high'],
                ],
                'is_active' => true,
                'order' => 2,
                'run_count' => 12,
            ],
            [
                'name' => 'Notificar al supervisor si SLA vence',
                'description' => 'Cuando se actualiza una conversacion y el SLA esta por vencer, agregar nota.',
                'trigger_event' => 'conversation.updated',
                'conditions' => [
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'open'],
                ],
                'actions' => [
                    ['type' => 'add_note', 'value' => 'ALERTA: El SLA esta proximo a vencer. Revisar con el supervisor.'],
                ],
                'is_active' => true,
                'order' => 3,
                'run_count' => 8,
            ],
            [
                'name' => 'Auto-respuesta fuera de horario',
                'description' => 'Enviar email automatico cuando se recibe mensaje fuera del horario de atencion.',
                'trigger_event' => 'message.created',
                'conditions' => [],
                'actions' => [
                    ['type' => 'send_email', 'value' => 'Gracias por contactarnos. Nuestro horario es lunes a viernes de 9 a 18h. Te responderemos el proximo dia habil.'],
                    ['type' => 'add_tag',    'value' => 'fuera-horario'],
                ],
                'is_active' => false,
                'order' => 10,
                'run_count' => 0,
            ],
            [
                'name' => 'Cerrar conversaciones resueltas sin respuesta',
                'description' => 'Cuando se marca como resuelta, agregar etiqueta de seguimiento.',
                'trigger_event' => 'conversation.resolved',
                'conditions' => [],
                'actions' => [
                    ['type' => 'add_tag', 'value' => 'resuelta'],
                ],
                'is_active' => true,
                'order' => 5,
                'run_count' => 134,
            ],
        ];

        foreach ($rules as $rule) {
            AutomationRule::firstOrCreate(['name' => $rule['name']], $rule);
        }
    }

    private function seedMacros(): void
    {
        $macros = [
            [
                'name' => 'Resolver y agradecer',
                'description' => 'Enviar respuesta de agradecimiento y resolver la conversacion.',
                'actions' => [
                    ['type' => 'send_reply',           'value' => 'Gracias por contactarnos. Hemos resuelto su solicitud. Si tiene alguna otra consulta, no dude en escribirnos.'],
                    ['type' => 'resolve_conversation', 'value' => null],
                    ['type' => 'add_tag',              'value' => 'resuelta'],
                ],
                'is_shared' => true,
                'is_active' => true,
                'usage_count' => 89,
            ],
            [
                'name' => 'Escalar a soporte técnico',
                'description' => 'Reasignar la conversacion al grupo de soporte tecnico nivel 2.',
                'actions' => [
                    ['type' => 'assign_group',    'value' => 'Soporte Técnico N2'],
                    ['type' => 'change_priority', 'value' => 'high'],
                    ['type' => 'add_tag',         'value' => 'escalada'],
                    ['type' => 'add_note',        'value' => 'Escalada a Soporte Técnico N2 para revisión avanzada.'],
                ],
                'is_shared' => true,
                'is_active' => true,
                'usage_count' => 34,
            ],
            [
                'name' => 'Solicitar más información',
                'description' => 'Pedir al cliente datos adicionales para procesar la solicitud.',
                'actions' => [
                    ['type' => 'send_reply', 'value' => 'Para poder ayudarle, necesitamos información adicional: (1) Número de pedido, (2) Fecha del incidente, (3) Capturas de pantalla si aplica.'],
                    ['type' => 'add_tag',    'value' => 'pendiente-info'],
                ],
                'is_shared' => true,
                'is_active' => true,
                'usage_count' => 156,
            ],
            [
                'name' => 'Cierre por inactividad',
                'description' => 'Cerrar conversaciones sin respuesta del cliente.',
                'actions' => [
                    ['type' => 'send_reply',         'value' => 'Cerramos este ticket por inactividad. Si necesita seguimiento, abra un nuevo ticket o contáctenos.'],
                    ['type' => 'close_conversation', 'value' => null],
                    ['type' => 'add_tag',            'value' => 'cerrada-inactividad'],
                ],
                'is_shared' => true,
                'is_active' => true,
                'usage_count' => 22,
            ],
            [
                'name' => 'Asignarme esta conversacion',
                'description' => 'Asignar la conversacion al agente actual y marcarlo como en progreso.',
                'actions' => [
                    ['type' => 'assign_agent',  'value' => 'me'],
                    ['type' => 'change_status', 'value' => 'in_progress'],
                ],
                'is_shared' => false,
                'is_active' => true,
                'usage_count' => 5,
            ],
        ];

        foreach ($macros as $macro) {
            Macro::firstOrCreate(['name' => $macro['name']], $macro);
        }
    }

    private function seedCannedReplies(): void
    {
        $replies = [
            [
                'title' => 'Saludo inicial',
                'body' => 'Hola {{contact.name}}, gracias por contactar a {{company.name}}. Mi nombre es {{agent.name}} y estaré encantado de ayudarte hoy.',
                'shortcut' => 'saludo',
                'category' => 'General',
                'is_global' => true,
                'usage_count' => 312,
            ],
            [
                'title' => 'Solicitar número de pedido',
                'body' => 'Hola {{contact.name}}, para revisar el estado de tu pedido necesito que me compartas el número de orden. Lo encontrarás en el email de confirmación de compra.',
                'shortcut' => 'pedido',
                'category' => 'Ventas',
                'is_global' => true,
                'usage_count' => 198,
            ],
            [
                'title' => 'Problema en revisión',
                'body' => 'Hola {{contact.name}}, hemos recibido tu reporte y nuestro equipo técnico ya está investigando el problema. Te notificaremos en cuanto tengamos una actualización. Gracias por tu paciencia.',
                'shortcut' => 'revision',
                'category' => 'Soporte',
                'is_global' => true,
                'usage_count' => 87,
            ],
            [
                'title' => 'Cierre satisfactorio',
                'body' => 'Me alegra saber que hemos resuelto tu consulta, {{contact.name}}. Si necesitas algo más en el futuro, no dudes en contactarnos.',
                'shortcut' => 'cierre',
                'category' => 'General',
                'is_global' => true,
                'usage_count' => 445,
            ],
            [
                'title' => 'Política de devoluciones',
                'body' => 'Nuestra política de devoluciones permite solicitar el reembolso dentro de los 30 días posteriores a la compra. El producto debe estar en su estado original.',
                'shortcut' => 'devolucion',
                'category' => 'Ventas',
                'is_global' => true,
                'usage_count' => 63,
            ],
            [
                'title' => 'Escalando a técnico',
                'body' => 'Hola {{contact.name}}, este caso requiere atención especializada. Voy a transferirte con nuestro equipo de soporte técnico nivel 2. Te contactarán en las próximas 2 horas hábiles.',
                'shortcut' => 'escalar',
                'category' => 'Soporte',
                'is_global' => true,
                'usage_count' => 29,
            ],
            [
                'title' => 'Fuera de horario',
                'body' => 'Gracias por contactarnos. Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 y sábados de 10:00 a 14:00. Tu mensaje será atendido el próximo día hábil.',
                'shortcut' => 'horario',
                'category' => 'General',
                'is_global' => true,
                'usage_count' => 71,
            ],
            [
                'title' => 'Resetear contraseña',
                'body' => 'Hola {{contact.name}}, para restablecer tu contraseña visita el enlace de recuperación e ingresa tu correo electrónico registrado. Recibirás las instrucciones en tu bandeja de entrada.',
                'shortcut' => 'password',
                'category' => 'Cuenta',
                'is_global' => true,
                'usage_count' => 142,
            ],
        ];

        foreach ($replies as $reply) {
            CannedReply::firstOrCreate(['shortcut' => $reply['shortcut']], $reply);
        }
    }
}
