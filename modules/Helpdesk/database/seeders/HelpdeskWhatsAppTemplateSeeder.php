<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Plantillas de ejemplo para desarrollo/demo del panel "Plantillas HSM".
 *
 * NINGUNA de estas se envió ni se aprobó realmente en Meta — status queda en
 * 'pending' a propósito. Ponerlo en 'approved' hace que aparezcan como
 * enviables en el panel del inbox y el envío real falla con
 * WhatsApp API error 132001 (#132001 Template name does not exist in the
 * translation), porque Meta no tiene ese name+language en su catálogo. Las
 * plantillas realmente aprobadas se sincronizan aparte desde Meta via
 * SyncWhatsAppTemplatesJob (GET a message_templates de la Graph API).
 */
class HelpdeskWhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $templates = [
            // Utility
            [
                'external_id' => 'bienvenida_cliente_v1',
                'display_name' => 'Bienvenida al cliente',
                'language' => 'es',
                'category' => 'utility',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => 'Hola {{1}} 👋',
                'body_template' => 'Gracias por contactar a *Alsernet*. Hemos recibido tu mensaje y un agente te atenderá en breve.\n\nTu número de caso es: *{{2}}*',
                'footer_text' => 'Alsernet • Soporte al cliente',
            ],
            [
                'external_id' => 'confirmacion_pedido_v1',
                'display_name' => 'Confirmación de pedido',
                'language' => 'es',
                'category' => 'utility',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '🛍️ Pedido confirmado',
                'body_template' => 'Hola {{1}}, tu pedido *#{{2}}* ha sido confirmado.\n\nFecha estimada de entrega: *{{3}}*\n\nPuedes rastrearlo aquí: {{4}}',
                'footer_text' => 'Alsernet • Logística',
            ],
            [
                'external_id' => 'recordatorio_pago_v1',
                'display_name' => 'Recordatorio de pago',
                'language' => 'es',
                'category' => 'utility',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '💳 Recordatorio de pago',
                'body_template' => 'Hola {{1}}, te recordamos que tienes un pago pendiente de *${{2}}* con vencimiento el *{{3}}*.\n\nPara pagar ingresa aquí: {{4}}\n\nSi ya realizaste el pago, ignora este mensaje.',
                'footer_text' => 'Alsernet • Facturación',
            ],
            [
                'external_id' => 'ticket_resuelto_v1',
                'display_name' => 'Ticket resuelto',
                'language' => 'es',
                'category' => 'utility',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '✅ Caso resuelto',
                'body_template' => 'Hola {{1}}, tu caso *#{{2}}* ha sido marcado como resuelto.\n\n_{{3}}_\n\nSi el problema persiste, responde a este mensaje y lo reabriremos.',
                'footer_text' => 'Alsernet • Soporte',
            ],
            [
                'external_id' => 'encuesta_satisfaccion_v1',
                'display_name' => 'Encuesta de satisfacción (CSAT)',
                'language' => 'es',
                'category' => 'utility',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '⭐ ¿Cómo te atendimos?',
                'body_template' => 'Hola {{1}}, tu opinión es importante para nosotros.\n\n¿Cómo calificarías la atención recibida?\n\nCalifica aquí (1 al 5): {{2}}',
                'footer_text' => 'Alsernet • Calidad',
            ],

            // Marketing
            [
                'external_id' => 'promo_temporada_v1',
                'display_name' => 'Promoción de temporada',
                'language' => 'es',
                'category' => 'marketing',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '🎉 Oferta especial para ti',
                'body_template' => 'Hola {{1}}, tenemos una oferta exclusiva que no querrás perderte.\n\n*{{2}}* — solo por tiempo limitado.\n\n👉 Aprovecha aquí: {{3}}\n\nVálido hasta el *{{4}}*.',
                'footer_text' => 'Alsernet • Promociones',
            ],
            [
                'external_id' => 'recuperacion_carrito_v1',
                'display_name' => 'Recuperación de carrito abandonado',
                'language' => 'es',
                'category' => 'marketing',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '🛒 ¿Olvidaste algo?',
                'body_template' => 'Hola {{1}}, notamos que dejaste artículos en tu carrito.\n\nTu carrito tiene *{{2}} producto(s)* por un total de *${{3}}*.\n\nRecupera tu compra aquí: {{4}}',
                'footer_text' => 'Alsernet • Tienda en línea',
            ],
            [
                'external_id' => 'reactivacion_cliente_v1',
                'display_name' => 'Reactivación de cliente inactivo',
                'language' => 'es',
                'category' => 'marketing',
                'status' => 'pending',
                'header_type' => 'text',
                'header_value' => '👋 ¡Te extrañamos!',
                'body_template' => 'Hola {{1}}, ha pasado un tiempo desde tu última visita. Queremos que sepas que tenemos novedades para ti.\n\n✨ *{{2}}*\n\nDescúbrelo aquí: {{3}}',
                'footer_text' => 'Alsernet • Fidelización',
            ],

            // Authentication
            [
                'external_id' => 'codigo_verificacion_v1',
                'display_name' => 'Código de verificación OTP',
                'language' => 'es',
                'category' => 'authentication',
                'status' => 'pending',
                'header_type' => null,
                'header_value' => null,
                'body_template' => 'Tu código de verificación de *Alsernet* es: *{{1}}*\n\nEste código expira en 10 minutos. No lo compartas con nadie.',
                'footer_text' => 'Si no solicitaste este código, ignora este mensaje.',
            ],
        ];

        foreach ($templates as $template) {
            DB::connection('helpdesk')->table('helpdesk_whatsapp_templates')->updateOrInsert(
                ['external_id' => $template['external_id']],
                array_merge($template, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('✅ Plantillas HSM/WhatsApp creadas ('.count($templates).')');
    }
}
