<?php

namespace Modules\Mailer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

class HelpdeskTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $lang = $this->resolveOrCreateLang();

        $templates = $this->templateDefinitions();

        foreach ($templates as $def) {
            $template = MailerTemplate::updateOrCreate(
                ['key' => $def['key']],
                [
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'module' => 'helpdesk',
                    'is_enabled' => true,
                    'is_protected' => false,
                    'variables' => $def['variables'],
                ]
            );

            if (! $template->uid) {
                $template->update(['uid' => (string) Str::uuid()]);
            }

            $templateLang = MailerTemplateLang::updateOrCreate(
                ['mailer_template_id' => $template->id, 'lang_id' => $lang->id],
                [
                    'subject' => $def['subject'],
                    'content' => $def['content'],
                ]
            );

            if (! $templateLang->uid) {
                $templateLang->update(['uid' => (string) Str::uuid()]);
            }

            $this->command->info("  ✓ {$def['name']} (key: {$def['key']})");
        }

        $this->command->info('✓ Helpdesk templates seeded ('.count($templates).' templates)');
    }

    private function resolveOrCreateLang(): MailerLang
    {
        $lang = MailerLang::where('iso_code', 'es')->first()
            ?? MailerLang::first();

        if ($lang) {
            return $lang;
        }

        return MailerLang::create([
            'uid' => (string) Str::uuid(),
            'title' => 'Español',
            'iso_code' => 'es',
            'lenguage_code' => 'es_ES',
            'locate' => 'es_ES',
            'available' => true,
        ]);
    }

    private function templateDefinitions(): array
    {
        $company = config('app.name', 'Soporte');

        return [
            [
                'key' => 'helpdesk.agent_reply',
                'name' => 'Respuesta del agente',
                'description' => 'Plantilla estándar para responder al cliente desde una conversación.',
                'subject' => 'Re: Tu consulta #{CONVERSATION_ID}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true, 'description' => 'ID de la conversación'],
                    ['name' => 'AGENT_NAME', 'required' => false, 'description' => 'Nombre del agente'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->agentReplyContent($company),
            ],
            [
                'key' => 'helpdesk.conversation_closed',
                'name' => 'Conversación cerrada',
                'description' => 'Notificación al cliente cuando se cierra su conversación.',
                'subject' => 'Tu consulta #{CONVERSATION_ID} ha sido resuelta',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true, 'description' => 'ID de la conversación'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->conversationClosedContent($company),
            ],
            [
                'key' => 'helpdesk.followup',
                'name' => 'Seguimiento',
                'description' => 'Correo de seguimiento para verificar si el cliente quedó satisfecho.',
                'subject' => 'Seguimiento a tu consulta #{CONVERSATION_ID}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true, 'description' => 'ID de la conversación'],
                    ['name' => 'AGENT_NAME', 'required' => false, 'description' => 'Nombre del agente'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->followupContent($company),
            ],
            [
                'key' => 'helpdesk.info_request',
                'name' => 'Solicitud de información',
                'description' => 'Correo solicitando información adicional al cliente para continuar gestionando su caso.',
                'subject' => 'Necesitamos más información — Consulta #{CONVERSATION_ID}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true, 'description' => 'ID de la conversación'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->infoRequestContent($company),
            ],
            [
                'key' => 'helpdesk.ticket_created',
                'name' => 'Confirmación de ticket recibido',
                'description' => 'Confirmación automática al cliente cuando se crea un ticket desde el widget o portal.',
                'subject' => 'Hemos recibido tu solicitud — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número único del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketCreatedContent($company),
            ],
            [
                'key' => 'helpdesk.ticket_reply',
                'name' => 'Respuesta del agente al ticket',
                'description' => 'Notificación al cliente cuando un agente responde a su ticket.',
                'subject' => 'El equipo de soporte respondió tu ticket #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número único del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'AGENT_NAME', 'required' => true, 'description' => 'Nombre del agente que respondió'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketReplyContent($company),
            ],
            [
                'key' => 'helpdesk.ticket_status_changed',
                'name' => 'Cambio de estado del ticket',
                'description' => 'Notificación al cliente cuando el estado de su ticket cambia.',
                'subject' => 'Tu ticket #{TICKET_NUMBER} cambió de estado',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número único del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'OLD_STATUS', 'required' => true, 'description' => 'Estado anterior del ticket'],
                    ['name' => 'NEW_STATUS', 'required' => true, 'description' => 'Nuevo estado del ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketStatusChangedContent($company),
            ],
            [
                'key' => 'helpdesk.new_ticket_agent',
                'name' => 'Nuevo ticket asignado al agente',
                'description' => 'Notificación a los agentes cuando se crea un nuevo ticket en el sistema.',
                'subject' => 'Nuevo ticket #{TICKET_NUMBER} — {PRIORITY}',
                'variables' => [
                    ['name' => 'AGENT_NAME', 'required' => true, 'description' => 'Nombre del agente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número único del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'PRIORITY', 'required' => true, 'description' => 'Prioridad del ticket'],
                    ['name' => 'SOURCE', 'required' => false, 'description' => 'Canal de origen del ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->newTicketAgentContent($company),
            ],
            [
                'key' => 'helpdesk.ticket_reopened',
                'name' => 'Ticket reabierto',
                'description' => 'Notificación al cliente cuando su ticket es reabierto después de estar cerrado.',
                'subject' => 'Tu ticket #{TICKET_NUMBER} ha sido reabierto',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número único del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketReopenedContent($company),
            ],
            [
                'key' => 'helpdesk.sla_escalation',
                'name' => 'Escalación SLA',
                'description' => 'Notificación interna cuando un ticket supera el umbral de escalación de SLA.',
                'subject' => '[ESCALACIÓN] Ticket #{TICKET_NUMBER} — SLA vencido',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true, 'description' => 'Número único del ticket'],
                    ['name' => 'SUBJECT', 'required' => true, 'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'DUE_AT', 'required' => true, 'description' => 'Fecha límite SLA'],
                    ['name' => 'BREACH_TIME', 'required' => true, 'description' => 'Tiempo desde el vencimiento'],
                    ['name' => 'COMPANY_NAME', 'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->slaEscalationContent($company),
            ],
        ];
    }

    private function agentReplyContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Gracias por contactarnos. Hemos revisado tu consulta y a continuación te respondemos.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;border-left:4px solid #b10100;padding-left:16px;background-color:#f9fafb;">
      &nbsp;
    </td>
  </tr>
  <tr>
    <td style="padding:16px 0 0;">
      Si tienes alguna pregunta adicional, no dudes en responder a este correo o contactarnos directamente.
    </td>
  </tr>
  <tr>
    <td style="padding:16px 0 0;">
      Un saludo,<br>
      <strong>{AGENT_NAME}</strong><br>
      <span style="font-size:13px;color:#6b7280;">{COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function conversationClosedContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Te informamos que tu consulta <strong>#{CONVERSATION_ID}</strong> ha sido marcada como resuelta.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f0fdf4;border-left:4px solid #16a34a;">
        <tr>
          <td style="padding:14px 16px;font-size:14px;color:#15803d;">
            Si consideras que tu consulta no ha sido resuelta completamente, por favor responde a este
            correo y reabriremos tu caso de inmediato.
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Gracias por confiar en nosotros.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <span style="font-size:13px;color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function followupContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Hace unos días atendimos tu consulta <strong>#{CONVERSATION_ID}</strong> y queremos asegurarnos
      de que quedaste satisfecho/a con la respuesta recibida.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      ¿Pudimos resolver tu consulta correctamente? Si necesitas algo más, estamos a tu disposición.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <strong>{AGENT_NAME}</strong><br>
      <span style="font-size:13px;color:#6b7280;">{COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function infoRequestContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Estamos gestionando tu consulta <strong>#{CONVERSATION_ID}</strong> y para poder ayudarte
      de la mejor manera posible necesitamos información adicional.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;font-size:14px;color:#374151;">
            Por favor, responde a este correo con los datos solicitados para que podamos continuar
            con la gestión de tu caso.
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Quedamos a la espera de tu respuesta. Muchas gracias.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <span style="font-size:13px;color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketReplyContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      <strong>{AGENT_NAME}</strong> de nuestro equipo de soporte ha respondido a tu ticket
      <strong>#{TICKET_NUMBER}</strong> con asunto <em>{SUBJECT}</em>.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;font-size:14px;color:#374151;">
            Para ver la respuesta completa y continuar la conversación, responde directamente
            a este correo o accede a tu panel de soporte.
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Si tienes alguna duda adicional, no dudes en contactarnos. Estamos aquí para ayudarte.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <strong>{AGENT_NAME}</strong><br>
      <span style="font-size:13px;color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketStatusChangedContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Te informamos que el estado de tu ticket <strong>#{TICKET_NUMBER}</strong>
      con asunto <em>{SUBJECT}</em> ha cambiado.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;width:130px;">Estado anterior</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{OLD_STATUS}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Nuevo estado</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;font-weight:bold;">{NEW_STATUS}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Si tienes alguna pregunta sobre este cambio, no dudes en responder a este correo.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <span style="font-size:13px;color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function newTicketAgentContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{AGENT_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Se ha creado un nuevo ticket en el sistema que requiere atención.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;width:130px;">Número de ticket</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Asunto</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{SUBJECT}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Cliente</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{CUSTOMER_NAME}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Prioridad</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;font-weight:bold;">{PRIORITY}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Canal de origen</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{SOURCE}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Por favor, accede al panel de soporte para revisar y gestionar este ticket lo antes posible.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <span style="font-size:13px;color:#6b7280;">Sistema de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketCreatedContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Hemos recibido tu solicitud correctamente. A continuación encontrarás el resumen de tu ticket:
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;width:130px;">Número de ticket</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Asunto</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{SUBJECT}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Nuestro equipo revisará tu solicitud y te responderá lo antes posible por este mismo correo.
      No necesitas hacer nada más por ahora.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <span style="font-size:13px;color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketReopenedContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>,</td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Te informamos que tu ticket <strong>#{TICKET_NUMBER}</strong> con asunto <em>{SUBJECT}</em>
      ha sido reabierto y nuestro equipo continuará trabajando en él.
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;width:130px;">Número de ticket</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Asunto</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{SUBJECT}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Nos pondremos en contacto contigo en cuanto tengamos novedades. Si necesitas añadir
      información adicional, puedes responder directamente a este correo.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      Un saludo,<br>
      <span style="font-size:13px;color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }

    private function slaEscalationContent(string $company): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#fef2f2;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:12px 16px;font-size:13px;color:#991b1b;font-weight:bold;">
            ESCALACIÓN INTERNA — Este correo es exclusivamente para el equipo de soporte.
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      El siguiente ticket ha superado su umbral de SLA y requiere atención inmediata:
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;width:150px;">Número de ticket</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Asunto</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{SUBJECT}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Cliente</td>
                <td style="padding:4px 0;font-size:14px;color:#111827;">{CUSTOMER_NAME}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Fecha límite SLA</td>
                <td style="padding:4px 0;font-size:14px;color:#b10100;font-weight:bold;">{DUE_AT}</td>
              </tr>
              <tr>
                <td style="padding:4px 0;font-size:13px;color:#6b7280;">Tiempo vencido</td>
                <td style="padding:4px 0;font-size:14px;color:#b10100;font-weight:bold;">{BREACH_TIME}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Por favor, accede al panel de soporte y gestiona este ticket de forma prioritaria para
      evitar un mayor incumplimiento del acuerdo de nivel de servicio.
    </td>
  </tr>
  <tr>
    <td style="padding:8px 0 0;">
      <span style="font-size:13px;color:#6b7280;">Sistema de soporte — {COMPANY_NAME}</span>
    </td>
  </tr>
</table>
HTML;
    }
}
