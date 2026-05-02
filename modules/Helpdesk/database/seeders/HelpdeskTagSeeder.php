<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpdeskTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'Urgente',          'slug' => 'urgente',          'color' => '#dc3545', 'description' => 'Requiere atención inmediata',          'is_active' => 1],
            ['name' => 'Facturación',       'slug' => 'facturacion',      'color' => '#fd7e14', 'description' => 'Consultas de pago o factura',           'is_active' => 1],
            ['name' => 'Soporte técnico',   'slug' => 'soporte-tecnico',  'color' => '#0d6efd', 'description' => 'Problemas técnicos o bugs',             'is_active' => 1],
            ['name' => 'Devolución',        'slug' => 'devolucion',       'color' => '#6f42c1', 'description' => 'Solicitud de devolución o reembolso',   'is_active' => 1],
            ['name' => 'Envío',             'slug' => 'envio',            'color' => '#20c997', 'description' => 'Seguimiento de envíos y logística',     'is_active' => 1],
            ['name' => 'VIP',               'slug' => 'vip',              'color' => '#ffc107', 'description' => 'Cliente prioritario',                   'is_active' => 1],
            ['name' => 'Primer contacto',   'slug' => 'primer-contacto',  'color' => '#0dcaf0', 'description' => 'Nueva consulta sin historial previo',   'is_active' => 1],
            ['name' => 'Escalado',          'slug' => 'escalado',         'color' => '#b10100', 'description' => 'Escalado a supervisor o área técnica',  'is_active' => 1],
            ['name' => 'En espera',         'slug' => 'en-espera',        'color' => '#6c757d', 'description' => 'Pendiente de respuesta del cliente',    'is_active' => 1],
            ['name' => 'Resuelto',          'slug' => 'resuelto',         'color' => '#13c672', 'description' => 'Problema confirmado como resuelto',     'is_active' => 1],
        ];

        $now = now();

        foreach ($tags as $tag) {
            DB::connection('helpdesk')->table('helpdesk_conversation_tags')->updateOrInsert(
                ['slug' => $tag['slug']],
                array_merge($tag, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info('✅ Etiquetas de conversación creadas ('.count($tags).')');
    }
}
