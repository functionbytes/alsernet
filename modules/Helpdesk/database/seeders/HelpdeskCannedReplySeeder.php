<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\CannedReply;

class HelpdeskCannedReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the helpdesk_canned_replies table with predefined response templates.
     * These templates allow support agents to quickly respond to common inquiries
     * with consistent, professional messaging.
     *
     * Templates are organized by category for easy discovery:
     * - Billing & Payments
     * - Technical Support
     * - Account Management
     * - Order & Shipping
     * - General Information
     * - Follow-up & Closing
     *
     * Depends on: None (independent)
     */
    public function run(): void
    {
        $replies = [
            // Billing & Payments
            [
                'uid' => Str::ulid(),
                'title' => 'Información de pago recibida',
                'key' => 'payment_received_info',
                'shortcut' => ':payment_info',
                'content' => 'Gracias por tu pago. Confirmo que hemos recibido tu transacción exitosamente. Tu recibo será enviado a tu correo electrónico en los próximos minutos.',
                'category' => 'billing_payments',
                'is_active' => true,
                'position' => 1,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Factura no recibida',
                'key' => 'invoice_not_received',
                'shortcut' => ':invoice_issue',
                'content' => 'Disculpa por el inconveniente. Por favor, verifica tu carpeta de spam o correo no deseado. Si aún no la encuentras, podré reenviarte la factura. Proporciona tu número de pedido o correo asociado.',
                'category' => 'billing_payments',
                'is_active' => true,
                'position' => 2,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Reembolso procesado',
                'key' => 'refund_processed',
                'shortcut' => ':refund_status',
                'content' => 'Tu reembolso ha sido aprobado y procesado. Según tu banco, puede tomar de 5 a 10 días hábiles para que aparezca en tu cuenta. El número de referencia del reembolso es: [REFUND_REF]',
                'category' => 'billing_payments',
                'is_active' => true,
                'position' => 3,
            ],

            // Technical Support
            [
                'uid' => Str::ulid(),
                'title' => 'Reiniciar navegador',
                'key' => 'restart_browser',
                'shortcut' => ':browser_restart',
                'content' => 'Gracias por reportar esto. Como primer paso, intenta: 1) Cerrar completamente el navegador 2) Borrar cookies y caché 3) Reiniciar tu navegador 4) Intentar nuevamente. Si el problema persiste, avísame.',
                'category' => 'technical_support',
                'is_active' => true,
                'position' => 4,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Problema técnico en investigación',
                'key' => 'technical_investigation',
                'shortcut' => ':tech_investigate',
                'content' => 'Lamento el inconveniente técnico que experimentas. He escalado este problema a nuestro equipo técnico para investigación. Te contactaré en las próximas 24 horas con una actualización. Referencia: [TICKET_REF]',
                'category' => 'technical_support',
                'is_active' => true,
                'position' => 5,
            ],

            // Account Management
            [
                'uid' => Str::ulid(),
                'title' => 'Instrucciones de reset de contraseña',
                'key' => 'password_reset_instructions',
                'shortcut' => ':password_reset',
                'content' => 'Para resetear tu contraseña: 1) Ve a la página de login 2) Haz clic en "Olvidé mi contraseña" 3) Ingresa tu correo electrónico 4) Revisa tu email para el enlace de confirmación 5) Crea una nueva contraseña segura.',
                'category' => 'account_management',
                'is_active' => true,
                'position' => 6,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Cuenta bloqueada por seguridad',
                'key' => 'account_locked_security',
                'shortcut' => ':account_locked',
                'content' => 'Tu cuenta ha sido temporalmente bloqueada por medidas de seguridad después de múltiples intentos de login fallidos. Esto es normal. Intenta nuevamente en 30 minutos o usa la opción de "Olvidé mi contraseña".',
                'category' => 'account_management',
                'is_active' => true,
                'position' => 7,
            ],

            // Order & Shipping
            [
                'uid' => Str::ulid(),
                'title' => 'Rastreo de pedido',
                'key' => 'order_tracking',
                'shortcut' => ':track_order',
                'content' => 'Tu pedido está en camino. Puedes rastrear tu envío usando este enlace: [TRACKING_URL] El número de seguimiento es: [TRACKING_NUMBER]. La entrega estimada es: [DELIVERY_DATE].',
                'category' => 'order_shipping',
                'is_active' => true,
                'position' => 8,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Retraso en envío',
                'key' => 'shipping_delay',
                'shortcut' => ':shipping_delay',
                'content' => 'Disculpa por el retraso en tu envío. Estamos investigando el problema con el transportista. Recibirás una actualización dentro de 24 horas. Como disculpa, ofrecemos [COMPENSATION]. ¿Hay algo más que pueda hacer?',
                'category' => 'order_shipping',
                'is_active' => true,
                'position' => 9,
            ],

            // General Information
            [
                'uid' => Str::ulid(),
                'title' => 'Política de devoluciones',
                'key' => 'returns_policy_info',
                'shortcut' => ':returns_policy',
                'content' => 'Aceptamos devoluciones dentro de 30 días de la compra. Los artículos deben estar sin usar y en su embalaje original. El envío de devolución es gratis con nuestras etiquetas. Más información: [RETURNS_LINK]',
                'category' => 'general_information',
                'is_active' => true,
                'position' => 10,
            ],

            // Follow-up & Closing
            [
                'uid' => Str::ulid(),
                'title' => 'Seguimiento de satisfacción',
                'key' => 'satisfaction_followup',
                'shortcut' => ':satisfaction',
                'content' => '¿Se ha resuelto tu problema? Nos encantaría saber tu opinión. Por favor, califica tu experiencia de soporte. Si aún tienes preguntas, no dudes en contactarnos nuevamente.',
                'category' => 'followup_closing',
                'is_active' => true,
                'position' => 11,
            ],
            [
                'uid' => Str::ulid(),
                'title' => 'Cierre automático de ticket',
                'key' => 'ticket_auto_close',
                'shortcut' => ':auto_close',
                'content' => 'Como no hemos recibido respuesta en 5 días, cerramos tu ticket. Si aún necesitas ayuda, responde a este mensaje o abre un nuevo ticket. ¡Estamos aquí para ayudarte!',
                'category' => 'followup_closing',
                'is_active' => true,
                'position' => 12,
            ],
        ];

        foreach ($replies as $reply) {
            CannedReply::firstOrCreate(
                ['key' => $reply['key']],
                $reply
            );
        }

        $this->command->info('✅ Helpdesk canned replies seeded successfully');
    }
}
