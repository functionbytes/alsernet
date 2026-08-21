<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Siembra las plantillas de email de HelpdeskTickets en el catálogo del módulo
 * Mailer (editables desde el admin), reemplazando los antiguos blades de
 * resources/views/emails. Los Mailables ahora transportan el HTML ya renderizado
 * por TicketMailRenderer desde estas plantillas.
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

        foreach ($this->templates() as $tpl) {
            $template = MailerTemplate::updateOrCreate(
                ['key' => $tpl['key']],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $tpl['name'],
                    'description' => $tpl['description'],
                    'module' => 'helpdesktickets',
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
                'key' => 'helpdesk_tickets.ticket_escalated',
                'name' => 'Ticket escalado',
                'description' => 'Aviso al agente cuando un ticket se escala automáticamente por inactividad.',
                'subject' => 'Ticket escalated: #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'OLD_PRIORITY', 'required' => true, 'description' => 'Prioridad anterior'],
                    ['name' => 'NEW_PRIORITY', 'required' => true, 'description' => 'Prioridad nueva'],
                    ['name' => 'ESCALATED_AT', 'required' => true, 'description' => 'Fecha/hora de escalado'],
                    ['name' => 'TICKET_URL', 'required' => true, 'description' => 'Enlace al ticket'],
                ],
                'content' => $this->ticketEscalatedContent(),
            ],
            [
                'key' => 'helpdesk_tickets.ticket_created',
                'name' => 'Ticket recibido',
                'description' => 'Confirmación al cliente de que su ticket ha sido recibido.',
                'subject' => 'Your ticket has been received — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'SUBMITTED_AT', 'required' => true, 'description' => 'Fecha/hora de creación'],
                ],
                'content' => $this->ticketCreatedContent(),
            ],
            [
                'key' => 'helpdesk_tickets.ticket_assigned',
                'name' => 'Ticket asignado',
                'description' => 'Aviso al agente de que se le ha asignado un ticket.',
                'subject' => 'Ticket assigned to you — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'AGENT_NAME', 'required' => true, 'description' => 'Nombre del agente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'CATEGORY', 'required' => false, 'description' => 'Categoría del ticket'],
                    ['name' => 'PRIORITY', 'required' => true, 'description' => 'Prioridad del ticket'],
                    ['name' => 'TICKET_URL', 'required' => true, 'description' => 'Enlace al ticket'],
                ],
                'content' => $this->ticketAssignedContent(),
            ],
            [
                'key' => 'helpdesk_tickets.sla_warning',
                'name' => 'Aviso de SLA',
                'description' => 'Aviso al agente cuando un ticket se acerca al límite de resolución SLA.',
                'subject' => 'SLA Warning — Ticket #{TICKET_NUMBER} ({PERCENT_USED}% used)',
                'variables' => [
                    ['name' => 'PERCENT_USED', 'required' => true, 'description' => '% del tiempo SLA consumido'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'DUE_AT', 'required' => true, 'description' => 'Fecha límite de resolución'],
                ],
                'content' => $this->slaWarningContent(),
            ],
            [
                'key' => 'helpdesk_tickets.sla_breach',
                'name' => 'Incumplimiento de SLA',
                'description' => 'Alerta al agente cuando un ticket incumple su tiempo de resolución SLA.',
                'subject' => 'SLA Breach Alert — Ticket #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número del ticket'],
                    ['name' => 'TICKET_SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'DUE_AT', 'required' => true, 'description' => 'Fecha límite de resolución'],
                ],
                'content' => $this->slaBreachContent(),
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
                ],
                'content' => $this->satisfactionSurveyContent(),
            ],
            [
                'key' => 'helpdesk_tickets.portal_magic_link',
                'name' => 'Enlace de acceso al portal',
                'description' => 'Enlace mágico de un solo uso para que el cliente acceda al portal de soporte.',
                'subject' => 'Your portal login link',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => false, 'description' => 'Nombre del cliente'],
                    ['name' => 'PORTAL_URL', 'required' => true, 'description' => 'Enlace de acceso al portal (un solo uso)'],
                ],
                'content' => $this->portalMagicLinkContent(),
            ],
        ];
    }

    private function ticketEscalatedContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket priority escalated</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #FA896B; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">Ticket priority escalated</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>A ticket assigned to you has been automatically escalated due to inactivity. Please review it immediately.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Customer</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Priority change</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">
                    <span style="color: #888;">{OLD_PRIORITY}</span>
                    &rarr;
                    <strong style="color: #FA896B;">{NEW_PRIORITY}</strong>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Escalated at</td>
                <td style="padding: 8px;">{ESCALATED_AT}</td>
            </tr>
        </table>
        <p style="text-align: center; margin: 20px 0;">
            <a href="{TICKET_URL}" style="background: #FA896B; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                Review ticket now
            </a>
        </p>
        <p style="color: #666; font-size: 13px;">Please resolve or update this ticket as soon as possible to maintain service quality.</p>
    </div>
</body>
</html>
HTML;
    }

    private function ticketCreatedContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Ticket Received</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #90bb13; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">We received your support request</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Thank you for contacting us. We have received your support request and our team will respond as soon as possible.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket number</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Submitted</td>
                <td style="padding: 8px;">{SUBMITTED_AT}</td>
            </tr>
        </table>
        <p style="color: #666; font-size: 14px;">Please keep this email for your records. You can reference ticket #{TICKET_NUMBER} in any future correspondence.</p>
    </div>
</body>
</html>
HTML;
    }

    private function ticketAssignedContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Ticket Assigned</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #13C672; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">A ticket has been assigned to you</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Hi {AGENT_NAME}, the following ticket has been assigned to you:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Customer</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Category</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{CATEGORY}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Priority</td>
                <td style="padding: 8px;">{PRIORITY}</td>
            </tr>
        </table>
        <p style="margin-top: 20px;">
            <a href="{TICKET_URL}" style="background: #13C672; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                View Ticket
            </a>
        </p>
    </div>
</body>
</html>
HTML;
    }

    private function slaWarningContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>SLA Warning</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #FEC90F; color: #333; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">SLA Warning — {PERCENT_USED}% of time used</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>The following ticket is approaching its SLA resolution deadline:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Customer</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Due At</td>
                <td style="padding: 8px; color: #d68910;">{DUE_AT}</td>
            </tr>
        </table>
        <p style="color: #666; font-size: 14px;">Please resolve this ticket soon to avoid an SLA breach.</p>
    </div>
</body>
</html>
HTML;
    }

    private function slaBreachContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>SLA Breach Alert</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #FA896B; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">SLA Breach Alert</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>The following ticket has breached its SLA resolution time:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%;">Ticket</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Subject</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{TICKET_SUBJECT}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Customer</td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold;">Due At</td>
                <td style="padding: 8px; color: #FA896B;">{DUE_AT}</td>
            </tr>
        </table>
        <p style="color: #666; font-size: 14px;">Please resolve this ticket immediately to maintain service quality.</p>
    </div>
</body>
</html>
HTML;
    }

    private function satisfactionSurveyContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Encuesta de satisfaccion</title></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #90bb13; color: white; padding: 15px 20px; border-radius: 4px 4px 0 0;">
        <h2 style="margin: 0;">Como fue tu experiencia?</h2>
    </div>
    <div style="border: 1px solid #ddd; border-top: none; padding: 20px; border-radius: 0 0 4px 4px;">
        <p>Tu ticket <strong>#{TICKET_NUMBER}</strong> ha sido cerrado.</p>
        <p>Nos gustaria saber como fue tu experiencia con nuestro soporte. Por favor selecciona una puntuacion:</p>
        <div style="text-align: center; margin: 24px 0;">{RATING_BUTTONS}</div>
        <p style="text-align: center; font-size: 13px; color: #888;">1 = Muy insatisfecho &nbsp;&nbsp; 5 = Muy satisfecho</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #666; font-size: 13px;">Ticket: #{TICKET_NUMBER} &mdash; {TICKET_SUBJECT}<br>Cerrado: {CLOSED_AT}</p>
    </div>
</body>
</html>
HTML;
    }

    private function portalMagicLinkContent(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Your portal login link</title></head>
<body style="font-family: Arial, sans-serif; background-color: #f5f6f8; padding: 40px 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; margin: 0 auto;">
        <tr>
            <td style="background-color: #ffffff; border-radius: 8px; padding: 40px; border: 1px solid #dee2e6;">
                <h2 style="margin: 0 0 8px; color: #212529; font-size: 20px;">Log in to Support Portal</h2>
                <p style="margin: 0 0 24px; color: #6c757d; font-size: 14px;">Hi {CUSTOMER_NAME},</p>
                <p style="color: #495057; margin: 0 0 24px;">Click the button below to log in to the Support Portal. This link is valid for 24 hours and can only be used once.</p>
                <p style="text-align: center; margin: 0 0 24px;">
                    <a href="{PORTAL_URL}" style="display: inline-block; background-color: #90bb13; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px;">Log in to portal</a>
                </p>
                <p style="color: #6c757d; font-size: 13px; margin: 0 0 8px;">Or copy and paste this link into your browser:</p>
                <p style="word-break: break-all; font-size: 12px; color: #adb5bd; margin: 0 0 24px;">{PORTAL_URL}</p>
                <hr style="border: none; border-top: 1px solid #dee2e6; margin: 24px 0;">
                <p style="color: #adb5bd; font-size: 12px; margin: 0;">This link expires in 24 hours. If you did not request this link, you can safely ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
