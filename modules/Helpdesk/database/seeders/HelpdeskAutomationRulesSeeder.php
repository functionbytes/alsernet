<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\AutomationRule;

class HelpdeskAutomationRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Asignar facturación a tickets de pago',
                'description' => 'Detecta palabras clave de facturación y asigna al equipo correspondiente',
                'trigger_event' => 'conversation.created',
                'conditions' => [
                    'operator' => 'OR',
                    'rules' => [
                        ['type' => 'content', 'operator' => 'contains', 'value' => 'factura'],
                        ['type' => 'content', 'operator' => 'contains', 'value' => 'pago'],
                        ['type' => 'content', 'operator' => 'contains', 'value' => 'cobro'],
                    ],
                ],
                'actions' => [
                    ['type' => 'add_label', 'params' => ['label' => 'facturacion']],
                    ['type' => 'change_priority', 'params' => ['priority' => 'high']],
                ],
                'order' => 10,
            ],
            [
                'name' => 'Auto-saludo en primer mensaje',
                'description' => 'Envía mensaje de bienvenida personalizado al crear conversación',
                'trigger_event' => 'conversation.created',
                'conditions' => ['operator' => 'AND', 'rules' => []],
                'actions' => [
                    ['type' => 'send_message', 'params' => [
                        'body' => 'Hola {{ contact.name | default: "" }}, gracias por contactarnos. Un agente te atenderá en breve.',
                        'is_internal' => false,
                    ]],
                ],
                'order' => 5,
            ],
            [
                'name' => 'Marcar urgentes',
                'description' => 'Aumenta prioridad cuando el cliente menciona urgencia',
                'trigger_event' => 'message.created',
                'conditions' => [
                    'operator' => 'OR',
                    'rules' => [
                        ['type' => 'content', 'operator' => 'contains', 'value' => 'urgente'],
                        ['type' => 'content', 'operator' => 'contains', 'value' => 'inmediato'],
                        ['type' => 'content', 'operator' => 'contains', 'value' => 'emergencia'],
                    ],
                ],
                'actions' => [
                    ['type' => 'change_priority', 'params' => ['priority' => 'urgent']],
                    ['type' => 'add_label', 'params' => ['label' => 'urgente']],
                ],
                'order' => 20,
            ],
            [
                'name' => 'Resolver conversaciones inactivas',
                'description' => 'Cierra conversaciones sin actividad en 72h',
                'trigger_event' => 'conversation.updated',
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['type' => 'status', 'operator' => 'equals', 'value' => 'open'],
                    ],
                ],
                'actions' => [
                    ['type' => 'add_private_note', 'params' => ['note' => 'Cerrada automáticamente por inactividad de 72h.']],
                    ['type' => 'change_status', 'params' => ['status' => 'resolved']],
                ],
                'order' => 100,
                'is_active' => false,
            ],
            [
                'name' => 'Notificar al manager si VIP',
                'description' => 'Envía notificación interna cuando un cliente VIP escribe',
                'trigger_event' => 'conversation.created',
                'conditions' => ['operator' => 'AND', 'rules' => []],
                'actions' => [
                    ['type' => 'add_private_note', 'params' => ['note' => 'Cliente potencialmente VIP — revisar Customer 360.']],
                ],
                'order' => 50,
                'is_active' => false,
            ],
        ];

        foreach ($rules as $r) {
            AutomationRule::query()->updateOrCreate(
                ['name' => $r['name']],
                array_merge($r, ['is_active' => $r['is_active'] ?? true])
            );
        }

        $this->command->info('Automation rules de demo creadas/actualizadas ('.count($rules).')');
    }
}
