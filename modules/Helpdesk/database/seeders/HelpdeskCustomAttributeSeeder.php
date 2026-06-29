<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpdeskCustomAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $attributes = [
            [
                'key' => 'company',
                'name' => 'Empresa',
                'customer_name' => 'Tu empresa',
                'description' => 'Empresa a la que pertenece el cliente',
                'customer_description' => 'Indícanos el nombre de tu empresa',
                'type' => 'customer',
                'format' => 'text',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => false, 'agentCanEdit' => true]),
                'config' => json_encode([]),
                'internal' => 0,
                'materialized' => 0,
                'active' => 1,
            ],
            [
                'key' => 'plan_subscribed',
                'name' => 'Plan suscrito',
                'customer_name' => 'Plan',
                'description' => 'Plan de suscripción del cliente',
                'customer_description' => null,
                'type' => 'customer',
                'format' => 'select',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => false, 'agentCanEdit' => true]),
                'config' => json_encode([
                    'options' => [
                        ['value' => 'free', 'label' => 'Free'],
                        ['value' => 'starter', 'label' => 'Starter'],
                        ['value' => 'pro', 'label' => 'Pro'],
                        ['value' => 'enterprise', 'label' => 'Enterprise'],
                    ],
                ]),
                'internal' => 0,
                'materialized' => 1,
                'active' => 1,
            ],
            [
                'key' => 'monthly_revenue',
                'name' => 'Ingreso mensual',
                'customer_name' => null,
                'description' => 'Valor mensual estimado del cliente (MRR)',
                'customer_description' => null,
                'type' => 'customer',
                'format' => 'number',
                'required' => 0,
                'permission' => json_encode(['userCanView' => false, 'userCanEdit' => false, 'agentCanEdit' => true]),
                'config' => json_encode(['min' => 0, 'step' => 1]),
                'internal' => 1,
                'materialized' => 1,
                'active' => 1,
            ],
            [
                'key' => 'is_vip',
                'name' => 'Cliente VIP',
                'customer_name' => null,
                'description' => 'Marca al cliente como VIP — recibe atención prioritaria',
                'customer_description' => null,
                'type' => 'customer',
                'format' => 'switch',
                'required' => 0,
                'permission' => json_encode(['userCanView' => false, 'userCanEdit' => false, 'agentCanEdit' => true]),
                'config' => json_encode([]),
                'internal' => 1,
                'materialized' => 1,
                'active' => 1,
            ],
            [
                'key' => 'industry',
                'name' => 'Sector',
                'customer_name' => 'Sector / industria',
                'description' => 'Sector económico al que pertenece el cliente',
                'customer_description' => '¿En qué sector trabajas?',
                'type' => 'customer',
                'format' => 'select',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => true, 'agentCanEdit' => true]),
                'config' => json_encode([
                    'options' => [
                        ['value' => 'retail', 'label' => 'Retail / comercio'],
                        ['value' => 'tech', 'label' => 'Tecnología'],
                        ['value' => 'finance', 'label' => 'Finanzas'],
                        ['value' => 'health', 'label' => 'Salud'],
                        ['value' => 'education', 'label' => 'Educación'],
                        ['value' => 'manufacturing', 'label' => 'Manufactura'],
                        ['value' => 'services', 'label' => 'Servicios'],
                        ['value' => 'other', 'label' => 'Otro'],
                    ],
                ]),
                'internal' => 0,
                'materialized' => 0,
                'active' => 1,
            ],
            [
                'key' => 'employee_count',
                'name' => 'Empleados',
                'customer_name' => 'Tamaño de la empresa',
                'description' => 'Número aproximado de empleados',
                'customer_description' => null,
                'type' => 'customer',
                'format' => 'select',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => true, 'agentCanEdit' => true]),
                'config' => json_encode([
                    'options' => [
                        ['value' => '1-10', 'label' => '1 a 10'],
                        ['value' => '11-50', 'label' => '11 a 50'],
                        ['value' => '51-200', 'label' => '51 a 200'],
                        ['value' => '201-1000', 'label' => '201 a 1000'],
                        ['value' => '1000+', 'label' => 'Más de 1000'],
                    ],
                ]),
                'internal' => 0,
                'materialized' => 0,
                'active' => 1,
            ],
            [
                'key' => 'satisfaction_rating',
                'name' => 'Satisfacción del cliente',
                'customer_name' => null,
                'description' => 'Valoración de 1 a 5 estrellas',
                'customer_description' => null,
                'type' => 'customer',
                'format' => 'rating',
                'required' => 0,
                'permission' => json_encode(['userCanView' => false, 'userCanEdit' => false, 'agentCanEdit' => true]),
                'config' => json_encode(['max' => 5]),
                'internal' => 1,
                'materialized' => 1,
                'active' => 1,
            ],
            [
                'key' => 'preferred_channel',
                'name' => 'Canal preferido',
                'customer_name' => '¿Cómo prefieres que te contactemos?',
                'description' => 'Canal preferido por el cliente',
                'customer_description' => null,
                'type' => 'customer',
                'format' => 'checkboxGroup',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => true, 'agentCanEdit' => true]),
                'config' => json_encode([
                    'options' => [
                        ['value' => 'email', 'label' => 'Email'],
                        ['value' => 'phone', 'label' => 'Teléfono'],
                        ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                        ['value' => 'chat', 'label' => 'Chat'],
                    ],
                ]),
                'internal' => 0,
                'materialized' => 0,
                'active' => 1,
            ],
            [
                'key' => 'order_reference',
                'name' => 'Referencia de pedido',
                'customer_name' => 'Nº de pedido',
                'description' => 'Número o referencia del pedido relacionado',
                'customer_description' => 'Si tu consulta es sobre un pedido, indica su número',
                'type' => 'conversation',
                'format' => 'text',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => true, 'agentCanEdit' => true]),
                'config' => json_encode([]),
                'internal' => 0,
                'materialized' => 0,
                'active' => 1,
            ],
            [
                'key' => 'urgency_level',
                'name' => 'Nivel de urgencia',
                'customer_name' => '¿Qué tan urgente es?',
                'description' => 'Urgencia auto-reportada por el cliente',
                'customer_description' => null,
                'type' => 'conversation',
                'format' => 'select',
                'required' => 0,
                'permission' => json_encode(['userCanView' => true, 'userCanEdit' => true, 'agentCanEdit' => true]),
                'config' => json_encode([
                    'options' => [
                        ['value' => 'low', 'label' => 'Baja'],
                        ['value' => 'normal', 'label' => 'Normal'],
                        ['value' => 'high', 'label' => 'Alta'],
                        ['value' => 'critical', 'label' => 'Crítica'],
                    ],
                ]),
                'internal' => 0,
                'materialized' => 0,
                'active' => 1,
            ],
        ];

        foreach ($attributes as $attr) {
            DB::connection('helpdesk')->table('helpdesk_attributes')->updateOrInsert(
                ['key' => $attr['key']],
                array_merge($attr, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info('Custom attributes creados ('.count($attributes).')');
    }
}
