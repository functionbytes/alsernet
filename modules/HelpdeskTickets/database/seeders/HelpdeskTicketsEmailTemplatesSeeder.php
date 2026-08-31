<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Siembra las plantillas de email de HelpdeskTickets en el catálogo del módulo
 * Mailer (editables desde el admin), reemplazando los antiguos blades de
 * resources/views/emails. Los Mailables ahora transportan el HTML ya renderizado
 * por TicketMailRenderer desde estas plantillas.
 *
 * Todas usan HelpdeskTicketsMailerLayoutSeeder::ALIAS (correr ese seeder antes)
 * — el contenido de cada plantilla es solo el FRAGMENTO interior (sin
 * <!DOCTYPE html>/<html>/<body>: eso lo aporta el layout), en español, y con
 * la paleta de marca (solo verdes/grises, nunca rojo — la app no usa rojo en
 * ningún estado): un único verde de marca (#90bb13) en todas, igual que
 * "Correo detectado → ticket creado"; "atención"/"crítico" se marca con un
 * emoji en el título, no con un color de alerta distinto.
 */
class HelpdeskTicketsEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command?->warn('No languages found - skipping helpdesk tickets email templates');

            return;
        }

        $layoutId = MailerLayout::where('alias', HelpdeskTicketsMailerLayoutSeeder::ALIAS)->value('id');

        foreach ($this->templates() as $tpl) {
            $template = MailerTemplate::updateOrCreate(
                ['key' => $tpl['key']],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $tpl['name'],
                    'description' => $tpl['description'],
                    'module' => 'helpdesktickets',
                    'layout_id' => $layoutId,
                    'is_enabled' => true,
                    'is_protected' => true,
                    'variables' => $tpl['variables'],
                ]
            );

            foreach ($langs as $lang) {
                MailerTemplateLang::updateOrCreate(
                    ['mailer_template_id' => $template->id, 'lang_id' => $lang->id],
                    ['subject' => $tpl['subject'], 'content' => $tpl['content']]
                );
            }

            $this->command?->info("Template: {$template->name} (ID: {$template->id})");
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'key' => 'helpdesk_tickets.ticket_created',
                'name' => 'Ticket recibido',
                'description' => 'Confirmación al cliente de que su ticket ha sido recibido.',
                'subject' => 'Hemos recibido tu solicitud — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'SUBMITTED_AT', 'required' => true, 'description' => 'Fecha/hora de creación'],
                    ['name' => 'MESSAGE_PREVIEW', 'required' => false, 'description' => 'Extracto del mensaje original del cliente'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa (para el pie del correo)'],
                ],
                'content' => $this->headerCard('#90bb13', 'Hemos recibido tu solicitud').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Gracias por contactarnos. Hemos recibido tu solicitud de soporte y nuestro equipo te responderá lo antes posible.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Número de ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Recibido</td>
            <td style="padding: 8px 0;">{SUBMITTED_AT}</td>
        </tr>
    </table>
    <div style="background: #f7f9f2; border-left: 3px solid #90bb13; padding: 12px 16px; margin: 0 0 16px; color: #555; font-size: 14px;">
        {MESSAGE_PREVIEW}
    </div>
    <p style="color: #666; font-size: 13px; margin: 0;">Conserva este correo como referencia. Puedes mencionar el ticket #{TICKET_NUMBER} en cualquier comunicación futura sobre este caso, o responder directamente a este correo.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk.ticket_reply',
                'name' => 'Respuesta del agente',
                'description' => 'Notifica al cliente cuando un agente responde su ticket (mensaje o comentario externo). Incluye el texto de la respuesta.',
                'subject' => 'Re: {SUBJECT} — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'AGENT_NAME', 'required' => false, 'description' => 'Nombre del agente que respondió'],
                    ['name' => 'MESSAGE_BODY', 'required' => true, 'description' => 'Texto de la respuesta (HTML, con saltos de línea), ya traducido al idioma del cliente si aplica'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Tenés una respuesta nueva').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, {AGENT_NAME} respondió tu ticket #{TICKET_NUMBER}:</p>
    <div style="background: #f6f7f1; border-left: 3px solid #90bb13; padding: 12px 16px; margin: 0 0 16px; border-radius: 0 6px 6px 0;">
        {MESSAGE_BODY}
    </div>
    <p style="color: #666; font-size: 13px; margin: 0;">Podés responder directamente a este correo para continuar la conversación.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk.ticket_status_changed',
                'name' => 'Cambio de estado del ticket',
                'description' => 'Aviso al cliente cuando el estado de su ticket cambia (en proceso, cerrado, etc.).',
                'subject' => 'Tu ticket #{TICKET_NUMBER} cambió a: {NEW_STATUS}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'OLD_STATUS', 'required' => true, 'description' => 'Estado anterior'],
                    ['name' => 'NEW_STATUS', 'required' => true, 'description' => 'Estado nuevo'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Tu ticket cambió de estado').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, el estado de tu ticket se actualizó.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER} — {SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Estado</td>
            <td style="padding: 8px 0;">
                <span style="color: #999;">{OLD_STATUS}</span>
                &rarr;
                <strong style="color: #90bb13;">{NEW_STATUS}</strong>
            </td>
        </tr>
    </table>
    <p style="color: #666; font-size: 13px; margin: 0;">Si necesitás agregar información, respondé directamente a este correo.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk.ticket_reopened',
                'name' => 'Ticket reabierto',
                'description' => 'Aviso al cliente cuando su ticket cerrado se reabre.',
                'subject' => 'Tu ticket #{TICKET_NUMBER} fue reabierto',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Retomamos tu caso').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, tu ticket fue reabierto y nuestro equipo lo está retomando.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0;">{SUBJECT}</td>
        </tr>
    </table>
    <p style="color: #666; font-size: 13px; margin: 0;">Te responderemos a la brevedad. Podés responder este correo para agregar cualquier detalle nuevo.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk.ticket_merged',
                'name' => 'Ticket fusionado',
                'description' => 'Aviso al cliente cuando su ticket se fusiona con otro ya existente.',
                'subject' => 'Tu ticket #{TICKET_NUMBER} se unió a otro caso',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket original'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket original'],
                    ['name' => 'TARGET_TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket destino'],
                    ['name' => 'TICKET_URL', 'required' => true, 'description' => 'Enlace al ticket destino en el portal'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Unimos tu caso a uno existente').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, notamos que tu consulta está relacionada con otro caso que ya tenías abierto, así que las unimos para darte una respuesta más completa.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket original</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER} — {SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Continúa en</td>
            <td style="padding: 8px 0;">#{TARGET_TICKET_NUMBER}</td>
        </tr>
    </table>
    <p style="margin: 0 0 16px;">
        <a href="{TICKET_URL}" style="background: #90bb13; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold; display: inline-block;">
            Ver caso #{TARGET_TICKET_NUMBER}
        </a>
    </p>
    <p style="color: #666; font-size: 13px; margin: 0;">A partir de ahora, respondé sobre el caso #{TARGET_TICKET_NUMBER} para que sigamos el hilo correcto.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk_tickets.ticket_assigned',
                'name' => 'Ticket asignado',
                'description' => 'Aviso al agente de que se le ha asignado un ticket.',
                'subject' => 'Se te asignó un ticket — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'AGENT_NAME', 'required' => true, 'description' => 'Nombre del agente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'CATEGORY', 'required' => false, 'description' => 'Categoría del ticket'],
                    ['name' => 'PRIORITY', 'required' => true, 'description' => 'Prioridad del ticket'],
                    ['name' => 'TICKET_URL', 'required' => true, 'description' => 'Enlace al ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Se te asignó un ticket').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {AGENT_NAME}, se te asignó el siguiente ticket:</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 20px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Cliente</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Categoría</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{CATEGORY}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Prioridad</td>
            <td style="padding: 8px 0;">{PRIORITY}</td>
        </tr>
    </table>
    <p style="margin: 0;">
        <a href="{TICKET_URL}" style="background: #90bb13; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold;">
            Ver ticket
        </a>
    </p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk_tickets.ticket_escalated',
                'name' => 'Ticket escalado',
                'description' => 'Aviso al agente cuando un ticket se escala automáticamente por inactividad.',
                'subject' => '⚠️ Ticket escalado: #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'OLD_PRIORITY', 'required' => true, 'description' => 'Prioridad anterior'],
                    ['name' => 'NEW_PRIORITY', 'required' => true, 'description' => 'Prioridad nueva'],
                    ['name' => 'ESCALATED_AT', 'required' => true, 'description' => 'Fecha/hora de escalado'],
                    ['name' => 'TICKET_URL', 'required' => true, 'description' => 'Enlace al ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', '⚠️ Ticket escalado por inactividad').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Un ticket asignado a vos se escaló automáticamente por inactividad. Revisalo cuanto antes.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 20px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Cliente</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Cambio de prioridad</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                <span style="color: #999;">{OLD_PRIORITY}</span>
                &rarr;
                <strong style="color: #90bb13;">{NEW_PRIORITY}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Escalado el</td>
            <td style="padding: 8px 0;">{ESCALATED_AT}</td>
        </tr>
    </table>
    <p style="margin: 0 0 12px;">
        <a href="{TICKET_URL}" style="background: #90bb13; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold; display: inline-block;">
            Revisar ticket ahora
        </a>
    </p>
    <p style="color: #666; font-size: 13px; margin: 0;">Resolvé o actualizá este ticket lo antes posible para mantener la calidad del servicio.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk_tickets.sla_warning',
                'name' => 'Aviso de SLA',
                'description' => 'Aviso al agente cuando un ticket se acerca al límite de resolución SLA.',
                'subject' => 'Aviso de SLA — Ticket #{TICKET_NUMBER} ({PERCENT_USED}% consumido)',
                'variables' => [
                    ['name' => 'PERCENT_USED', 'required' => true, 'description' => '% del tiempo SLA consumido'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'DUE_AT', 'required' => true, 'description' => 'Fecha límite de resolución'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Aviso de SLA — {PERCENT_USED}% del tiempo usado').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">El siguiente ticket se está acercando a su límite de resolución SLA:</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Cliente</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Vence</td>
            <td style="padding: 8px 0; color: #90bb13; font-weight: bold;">{DUE_AT}</td>
        </tr>
    </table>
    <p style="color: #666; font-size: 13px; margin: 0;">Resolvé este ticket pronto para evitar un incumplimiento de SLA.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk_tickets.sla_breach',
                'name' => 'Incumplimiento de SLA',
                'description' => 'Alerta al agente cuando un ticket incumple su tiempo de resolución SLA.',
                'subject' => '⚠️ Incumplimiento de SLA — Ticket #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'DUE_AT', 'required' => true, 'description' => 'Fecha límite de resolución'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', '⚠️ Incumplimiento de SLA').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">El siguiente ticket incumplió su tiempo de resolución SLA:</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Cliente</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Vencía</td>
            <td style="padding: 8px 0; color: #90bb13; font-weight: bold;">{DUE_AT}</td>
        </tr>
    </table>
    <p style="color: #666; font-size: 13px; margin: 0;">Resolvé este ticket de inmediato para mantener la calidad del servicio.</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk_tickets.satisfaction_survey',
                'name' => 'Encuesta de satisfacción',
                'description' => 'Encuesta CSAT enviada al cliente al cerrar su ticket.',
                'subject' => 'Cuéntanos tu experiencia — Ticket #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CLOSED_AT', 'required' => false, 'description' => 'Fecha/hora de cierre'],
                    ['name' => 'RATING_BUTTONS', 'required' => true, 'description' => 'Botones de puntuación 1-5 (HTML pre-renderizado con enlaces firmados)'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', '¿Cómo fue tu experiencia?').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 8px;">Tu ticket <strong>#{TICKET_NUMBER}</strong> fue cerrado.</p>
    <p style="margin: 0 0 16px;">Nos gustaría saber cómo fue tu experiencia con nuestro soporte. Por favor seleccioná una puntuación:</p>
    <div style="text-align: center; margin: 24px 0;">{RATING_BUTTONS}</div>
    <p style="text-align: center; font-size: 13px; color: #888; margin: 0 0 16px;">1 = Muy insatisfecho &nbsp;&nbsp; 5 = Muy satisfecho</p>
    <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="color: #666; font-size: 13px; margin: 0;">Ticket: #{TICKET_NUMBER} &mdash; {TICKET_SUBJECT}<br>Cerrado: {CLOSED_AT}</p>
</div>
HTML,
            ],
            [
                'key' => 'helpdesk_tickets.portal_magic_link',
                'name' => 'Enlace de acceso al portal',
                'description' => 'Enlace mágico de un solo uso para que el cliente acceda al portal de soporte.',
                'subject' => 'Tu enlace de acceso al portal',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'PORTAL_URL', 'required' => true, 'description' => 'Enlace de acceso al portal (un solo uso)'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->headerCard('#90bb13', 'Acceso al portal de soporte').<<<'HTML'
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola {CUSTOMER_NAME}, hacé clic en el botón para acceder al portal de soporte. Este enlace es válido por 24 horas y solo puede usarse una vez.</p>
    <p style="text-align: center; margin: 0 0 16px;">
        <a href="{PORTAL_URL}" style="display: inline-block; background-color: #90bb13; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px;">Ingresar al portal</a>
    </p>
    <p style="color: #6c757d; font-size: 13px; margin: 0 0 4px;">O copiá y pegá este enlace en tu navegador:</p>
    <p style="word-break: break-all; font-size: 12px; color: #adb5bd; margin: 0 0 16px;">{PORTAL_URL}</p>
    <p style="color: #adb5bd; font-size: 12px; margin: 0;">Si no solicitaste este acceso, podés ignorar este correo.</p>
</div>
HTML,
            ],
        ];
    }

    /**
     * Barra de título con el verde de marca (#90bb13), el mismo en las 9
     * plantillas — nunca rojo/naranja: [[feedback_palette_no_reds_only_greens]].
     * "Atención" se marca con un emoji en el título, no con un color distinto.
     */
    private function headerCard(string $color, string $title): string
    {
        return <<<HTML
<div style="background: {$color}; color: #ffffff; padding: 20px 24px;">
    <h2 style="margin: 0; font-size: 18px;">{$title}</h2>
</div>
HTML;
    }
}
