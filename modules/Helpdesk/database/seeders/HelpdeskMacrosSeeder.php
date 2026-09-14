<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\Macro;

class HelpdeskMacrosSeeder extends Seeder
{
    public function run(): void
    {
        $macros = [
            [
                'name' => 'Bienvenida estándar',
                'description' => 'Saluda al cliente y promete atención en X minutos',
                'is_shared' => true,
                'is_active' => true,
                'actions' => [
                    ['type' => 'send_reply', 'params' => [
                        'body' => 'Hola {{ contact.name | default: "" }}, soy {{ agent.firstname }}. Te ayudaré con tu consulta. ¿En qué puedo asistirte?',
                    ]],
                ],
            ],
            [
                'name' => 'Resolver con feedback',
                'description' => 'Marca como resuelta y solicita valoración',
                'is_shared' => true,
                'is_active' => true,
                'actions' => [
                    ['type' => 'send_reply', 'params' => [
                        'body' => 'He resuelto tu consulta. Si necesitas algo más, no dudes en escribir. ¿Cómo valorarías esta atención?',
                    ]],
                    ['type' => 'add_tag', 'params' => ['label' => 'resuelta']],
                    ['type' => 'resolve_conversation', 'params' => []],
                ],
            ],
            [
                'name' => 'Escalar a soporte técnico',
                'description' => 'Transfiere al equipo técnico con nota interna',
                'is_shared' => true,
                'is_active' => true,
                'actions' => [
                    ['type' => 'add_note', 'params' => [
                        'note' => 'Escalado por {{ agent.firstname }} — requiere análisis técnico.',
                    ]],
                    ['type' => 'assign_group', 'params' => ['group' => 'technical_support']],
                    ['type' => 'change_priority', 'params' => ['priority' => 'high']],
                ],
            ],
            [
                'name' => 'Solicitar más información',
                'description' => 'Pide datos adicionales al cliente',
                'is_shared' => true,
                'is_active' => true,
                'actions' => [
                    ['type' => 'send_reply', 'params' => [
                        'body' => "Para ayudarte mejor necesitamos más información. Por favor compártenos:\n- Número de pedido o referencia\n- Captura de pantalla del problema\n- Fecha aproximada del incidente",
                    ]],
                ],
            ],
        ];

        foreach ($macros as $m) {
            Macro::query()->updateOrCreate(
                ['name' => $m['name']],
                $m
            );
        }

        $this->command->info('Macros de demo creados ('.count($macros).')');
    }
}
