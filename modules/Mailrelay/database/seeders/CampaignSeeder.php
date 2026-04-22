<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Enums\CampaignStatus;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener provider por defecto
        $defaultProvider = MailProvider::where('is_default', true)->first();

        if (! $defaultProvider) {
            $this->command->warn('⚠️  No hay mail provider por defecto. Ejecuta MailProviderSeeder primero.');

            return;
        }

        // Obtener un template de ejemplo de Mailer (si existe)
        $mailerTemplate = MailerTemplate::where('module', 'core')
            ->where('is_enabled', true)
            ->first();

        // Campaign 1: Newsletter mensual usando MailerTemplate
        Campaign::create([
            'name' => 'Newsletter Mensual - Enero 2026',
            'subject' => '📰 Newsletter Alsernet - Novedades de Enero',
            'mailer_template_id' => $mailerTemplate?->id,
            'lang_id' => 1,
            'mail_provider_id' => $defaultProvider->id,
            'template_variables' => [
                'CAMPAIGN_MONTH' => 'Enero',
                'CAMPAIGN_YEAR' => '2026',
                'FEATURED_CONTENT' => '<h3>Nuevas funcionalidades</h3><p>Hemos lanzado mejoras importantes...</p>',
            ],
            'track_opens' => true,
            'track_clicks' => true,
            'status' => CampaignStatus::DRAFT,
            'metadata' => [
                'category' => 'newsletter',
                'tags' => ['monthly', 'updates', 'news'],
            ],
        ]);

        // Campaign 2: Promoción especial con HTML custom
        Campaign::create([
            'name' => 'Promoción Especial - 50% Descuento',
            'subject' => '🎉 ¡Última Oportunidad! 50% de Descuento',
            'html_content' => $this->getPromotionalEmailHtml(),
            'mail_provider_id' => $defaultProvider->id,
            'track_opens' => true,
            'track_clicks' => true,
            'status' => CampaignStatus::DRAFT,
            'metadata' => [
                'category' => 'promotional',
                'tags' => ['sale', 'discount', 'limited-time'],
                'discount_percentage' => 50,
            ],
        ]);

        // Campaign 3: Bienvenida para nuevos usuarios usando template
        Campaign::create([
            'name' => 'Bienvenida Nuevos Usuarios',
            'subject' => '👋 ¡Bienvenido a Alsernet!',
            'mailer_template_id' => $mailerTemplate?->id,
            'lang_id' => 1,
            'mail_provider_id' => $defaultProvider->id,
            'template_variables' => [
                'WELCOME_MESSAGE' => 'Gracias por unirte a nuestra comunidad',
                'GETTING_STARTED_URL' => 'https://alsernet.com/getting-started',
            ],
            'track_opens' => true,
            'track_clicks' => true,
            'status' => CampaignStatus::DRAFT,
            'metadata' => [
                'category' => 'onboarding',
                'tags' => ['welcome', 'new-user'],
                'auto_send' => true,
            ],
        ]);

        // Campaign 4: Campaña programada (ejemplo)
        Campaign::create([
            'name' => 'Recordatorio Webinar - Febrero 2026',
            'subject' => '📅 No olvides: Webinar mañana a las 10:00',
            'html_content' => $this->getWebinarReminderHtml(),
            'mail_provider_id' => $defaultProvider->id,
            'scheduled_at' => now()->addDays(7), // Programado para dentro de 7 días
            'track_opens' => true,
            'track_clicks' => true,
            'status' => CampaignStatus::SCHEDULED,
            'metadata' => [
                'category' => 'event',
                'tags' => ['webinar', 'reminder', 'education'],
                'webinar_date' => now()->addDays(8)->format('Y-m-d H:i:s'),
            ],
        ]);

        $this->command->info('✅ Campaigns de ejemplo creadas exitosamente');
    }

    /**
     * HTML de email promocional
     */
    private function getPromotionalEmailHtml(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #90bb13 0%, #7a9f11 100%); color: white; padding: 40px 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .cta-button { display: inline-block; background: #90bb13; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { background: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0; font-size: 32px;">🎉 ¡Oferta Especial!</h1>
                    <p style="margin: 10px 0 0 0; font-size: 18px;">50% de Descuento por Tiempo Limitado</p>
                </div>

                <div class="content">
                    <p>Hola {SUBSCRIBER_FIRSTNAME},</p>

                    <p>Tenemos una noticia increíble para ti: <strong>50% de descuento</strong> en todos nuestros servicios durante las próximas 48 horas.</p>

                    <p>Esta es tu oportunidad para:</p>
                    <ul>
                        <li>Acceder a funcionalidades premium</li>
                        <li>Mejorar tu plan actual</li>
                        <li>Ahorrar mientras creces tu negocio</li>
                    </ul>

                    <div style="text-align: center;">
                        <a href="{PROMOTION_URL}" class="cta-button">Aprovechar Oferta Ahora</a>
                    </div>

                    <p style="font-size: 14px; color: #666; margin-top: 30px;">
                        <strong>Nota:</strong> Esta oferta expira el {EXPIRATION_DATE}
                    </p>
                </div>

                <div class="footer">
                    <p>Te has suscrito a nuestras newsletters de {SITE_NAME}</p>
                    <p><a href="{UNSUBSCRIBE_URL}" style="color: #90bb13;">Cancelar suscripción</a></p>
                    <p>{CURRENT_YEAR} {SITE_NAME}. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * HTML de recordatorio de webinar
     */
    private function getWebinarReminderHtml(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #90bb13; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: white; }
                .event-box { background: #f9f9f9; border-left: 4px solid #90bb13; padding: 20px; margin: 20px 0; }
                .cta { background: #90bb13; color: white; padding: 15px 40px; text-decoration: none; display: inline-block; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📅 Recordatorio de Webinar</h1>
                </div>

                <div class="content">
                    <p>Hola {SUBSCRIBER_FIRSTNAME},</p>

                    <p>Te recordamos que <strong>mañana</strong> tenemos nuestro webinar especial.</p>

                    <div class="event-box">
                        <h3 style="margin-top: 0;">Detalles del Evento</h3>
                        <p><strong>📆 Fecha:</strong> {WEBINAR_DATE}</p>
                        <p><strong>🕐 Hora:</strong> 10:00 AM (hora local)</p>
                        <p><strong>⏱️ Duración:</strong> 1 hora</p>
                        <p><strong>🎯 Tema:</strong> Estrategias de Email Marketing 2026</p>
                    </div>

                    <div style="text-align: center;">
                        <a href="{WEBINAR_URL}" class="cta">Unirme al Webinar</a>
                    </div>

                    <p>¡Nos vemos mañana!</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
