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

        foreach ($this->templateDefinitions() as $def) {
            $template = MailerTemplate::updateOrCreate(
                ['key' => $def['key']],
                [
                    'uid' => (string) Str::ulid(),
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'module' => 'helpdesk',
                    'is_enabled' => true,
                    'is_protected' => false,
                    'variables' => $def['variables'],
                ]
            );

            MailerTemplateLang::updateOrCreate(
                ['mailer_template_id' => $template->id, 'lang_id' => $lang->id],
                [
                    'uid' => (string) Str::ulid(),
                    'subject' => $def['subject'],
                    'content' => $def['content'],
                ]
            );

            $this->command->info("  ✓ {$def['name']} (key: {$def['key']})");
        }

        $this->command->info('✓ Helpdesk templates seeded ('.count($this->templateDefinitions()).' templates)');
    }

    private function resolveOrCreateLang(): MailerLang
    {
        return MailerLang::where('iso_code', 'es')->first()
            ?? MailerLang::first()
            ?? MailerLang::create([
                'uid' => (string) Str::ulid(),
                'title' => 'Español',
                'iso_code' => 'es',
                'lenguage_code' => 'es_ES',
                'locate' => 'es_ES',
                'available' => true,
            ]);
    }

    private function templateDefinitions(): array
    {
        return [
            // ── Conversaciones ─────────────────────────────────────────────
            [
                'key' => 'helpdesk.agent_reply',
                'name' => 'Respuesta del agente',
                'description' => 'Plantilla estándar para responder al cliente desde una conversación.',
                'subject' => 'Re: Tu consulta #{CONVERSATION_ID}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true,  'description' => 'ID de la conversación'],
                    ['name' => 'AGENT_NAME',      'required' => false, 'description' => 'Nombre del agente'],
                    ['name' => 'COMPANY_NAME',    'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->agentReplyContent(),
            ],
            [
                'key' => 'helpdesk.conversation_closed',
                'name' => 'Conversación cerrada',
                'description' => 'Notificación al cliente cuando se cierra su conversación.',
                'subject' => 'Tu consulta #{CONVERSATION_ID} ha sido resuelta',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME',   'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true,  'description' => 'ID de la conversación'],
                    ['name' => 'COMPANY_NAME',    'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->conversationClosedContent(),
            ],
            [
                'key' => 'helpdesk.followup',
                'name' => 'Seguimiento',
                'description' => 'Correo de seguimiento para verificar si el cliente quedó satisfecho.',
                'subject' => 'Seguimiento a tu consulta #{CONVERSATION_ID}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME',   'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true,  'description' => 'ID de la conversación'],
                    ['name' => 'AGENT_NAME',      'required' => false, 'description' => 'Nombre del agente'],
                    ['name' => 'COMPANY_NAME',    'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->followupContent(),
            ],
            [
                'key' => 'helpdesk.info_request',
                'name' => 'Solicitud de información',
                'description' => 'Correo solicitando información adicional al cliente para continuar gestionando su caso.',
                'subject' => 'Necesitamos más información — Consulta #{CONVERSATION_ID}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME',   'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'CONVERSATION_ID', 'required' => true,  'description' => 'ID de la conversación'],
                    ['name' => 'COMPANY_NAME',    'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->infoRequestContent(),
            ],

            // ── Tickets ────────────────────────────────────────────────────
            [
                'key' => 'helpdesk.ticket_created',
                'name' => 'Confirmación de ticket recibido',
                'description' => 'Confirmación automática al cliente cuando se crea su ticket.',
                'subject' => 'Hemos recibido tu solicitud — #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME',  'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'TICKET_NUMBER',  'required' => true,  'description' => 'Número de ticket'],
                    ['name' => 'SUBJECT',        'required' => true,  'description' => 'Asunto del ticket'],
                    ['name' => 'COMPANY_NAME',   'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketCreatedContent(),
            ],
            [
                'key' => 'helpdesk.ticket_reply',
                'name' => 'Respuesta del agente al ticket',
                'description' => 'Notificación al cliente cuando el agente responde su ticket.',
                'subject' => 'El equipo de soporte respondió tu ticket #{TICKET_NUMBER}',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'TICKET_NUMBER', 'required' => true,  'description' => 'Número de ticket'],
                    ['name' => 'SUBJECT',       'required' => true,  'description' => 'Asunto del ticket'],
                    ['name' => 'AGENT_NAME',    'required' => false, 'description' => 'Nombre del agente'],
                    ['name' => 'COMPANY_NAME',  'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketReplyContent(),
            ],
            [
                'key' => 'helpdesk.ticket_status_changed',
                'name' => 'Cambio de estado del ticket',
                'description' => 'Notificación al cliente cuando cambia el estado de su ticket.',
                'subject' => 'Tu ticket #{TICKET_NUMBER} cambió de estado',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'TICKET_NUMBER', 'required' => true,  'description' => 'Número de ticket'],
                    ['name' => 'SUBJECT',       'required' => true,  'description' => 'Asunto del ticket'],
                    ['name' => 'OLD_STATUS',    'required' => true,  'description' => 'Estado anterior'],
                    ['name' => 'NEW_STATUS',    'required' => true,  'description' => 'Nuevo estado'],
                    ['name' => 'COMPANY_NAME',  'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketStatusChangedContent(),
            ],
            [
                'key' => 'helpdesk.ticket_reopened',
                'name' => 'Ticket reabierto',
                'description' => 'Notificación al cliente cuando su ticket es reabierto.',
                'subject' => 'Tu ticket #{TICKET_NUMBER} ha sido reabierto',
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true,  'description' => 'Nombre del contacto'],
                    ['name' => 'TICKET_NUMBER', 'required' => true,  'description' => 'Número de ticket'],
                    ['name' => 'SUBJECT',       'required' => true,  'description' => 'Asunto del ticket'],
                    ['name' => 'COMPANY_NAME',  'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->ticketReopenedContent(),
            ],

            // ── Agentes ────────────────────────────────────────────────────
            [
                'key' => 'helpdesk.new_ticket_agent',
                'name' => 'Nuevo ticket asignado al agente',
                'description' => 'Notificación interna al agente cuando se le asigna un nuevo ticket.',
                'subject' => 'Nuevo ticket #{TICKET_NUMBER} — {PRIORITY}',
                'variables' => [
                    ['name' => 'AGENT_NAME',    'required' => true,  'description' => 'Nombre del agente'],
                    ['name' => 'TICKET_NUMBER', 'required' => true,  'description' => 'Número de ticket'],
                    ['name' => 'SUBJECT',       'required' => true,  'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => true,  'description' => 'Nombre del cliente'],
                    ['name' => 'PRIORITY',      'required' => true,  'description' => 'Prioridad del ticket'],
                    ['name' => 'SOURCE',        'required' => false, 'description' => 'Canal de origen'],
                    ['name' => 'COMPANY_NAME',  'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->newTicketAgentContent(),
            ],
            [
                'key' => 'helpdesk.sla_escalation',
                'name' => 'Escalación SLA',
                'description' => 'Alerta interna al equipo cuando un ticket supera el umbral de SLA.',
                'subject' => '[ESCALACIÓN] Ticket #{TICKET_NUMBER} — SLA vencido',
                'variables' => [
                    ['name' => 'TICKET_NUMBER', 'required' => true,  'description' => 'Número de ticket'],
                    ['name' => 'SUBJECT',       'required' => true,  'description' => 'Asunto del ticket'],
                    ['name' => 'CUSTOMER_NAME', 'required' => true,  'description' => 'Nombre del cliente'],
                    ['name' => 'DUE_AT',        'required' => true,  'description' => 'Fecha límite SLA'],
                    ['name' => 'BREACH_TIME',   'required' => true,  'description' => 'Tiempo vencido'],
                    ['name' => 'COMPANY_NAME',  'required' => false, 'description' => 'Nombre de la empresa'],
                ],
                'content' => $this->slaEscalationContent(),
            ],
        ];
    }

    // ── Contenidos ─────────────────────────────────────────────────────────

    private function agentReplyContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Gracias por contactarnos. Hemos revisado tu consulta y a continuación te respondemos:
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            &nbsp;
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Si tienes alguna pregunta adicional, no dudes en responder a este correo o contactarnos directamente.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales,<br>
            <strong>{AGENT_NAME}</strong><br>
            <span style="font-size:13px; color:#6b7280;">{COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function conversationClosedContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Te informamos que tu consulta <strong>#{CONVERSATION_ID}</strong> ha sido marcada como resuelta.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f0fdf4; border-left:4px solid #16a34a; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px; font-size:14px; color:#15803d;">
            Si consideras que tu consulta no ha sido resuelta completamente, por favor responde a este
            correo y reabriremos tu caso de inmediato.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">Gracias por confiar en nosotros.</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales.<br>
            <span style="font-size:13px; color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function followupContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Hace unos días atendimos tu consulta <strong>#{CONVERSATION_ID}</strong> y queremos
            asegurarnos de que quedaste satisfecho/a con la respuesta recibida.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            ¿Pudimos resolver tu consulta correctamente? Si necesitas algo más, estamos a tu disposición.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales,<br>
            <strong>{AGENT_NAME}</strong><br>
            <span style="font-size:13px; color:#6b7280;">{COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function infoRequestContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Estamos gestionando tu consulta <strong>#{CONVERSATION_ID}</strong> y para poder
            ayudarte de la mejor manera posible necesitamos información adicional.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px; font-size:14px; color:#374151;">
            Por favor, responde a este correo con los datos solicitados para que podamos continuar
            con la gestión de tu caso.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">Quedamos a la espera de tu respuesta. Muchas gracias.</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales.<br>
            <span style="font-size:13px; color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketCreatedContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            <strong>¡Hemos recibido tu solicitud correctamente!</strong><br>
            A continuación el resumen de tu ticket:
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280; width:130px;">Número de ticket</td>
                <td style="padding:4px 0; font-size:14px; color:#000; font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Asunto</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{SUBJECT}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Nuestro equipo revisará tu solicitud y te responderá lo antes posible por este mismo correo.
            No necesitas hacer nada más por ahora.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales.<br>
            <span style="font-size:13px; color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketReplyContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            <strong>{AGENT_NAME}</strong> de nuestro equipo de soporte ha respondido a tu ticket
            <strong>#{TICKET_NUMBER}</strong> con asunto <em>{SUBJECT}</em>.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px; font-size:14px; color:#374151;">
            Para ver la respuesta completa y continuar la conversación, responde directamente
            a este correo o accede a tu portal de soporte.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Si tienes alguna duda adicional, no dudes en contactarnos. Estamos aquí para ayudarte.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales,<br>
            <strong>{AGENT_NAME}</strong><br>
            <span style="font-size:13px; color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketStatusChangedContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Te informamos que el estado de tu ticket <strong>#{TICKET_NUMBER}</strong>
            con asunto <em>{SUBJECT}</em> ha cambiado.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280; width:130px;">Estado anterior</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{OLD_STATUS}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Nuevo estado</td>
                <td style="padding:4px 0; font-size:14px; color:#000; font-weight:bold;">{NEW_STATUS}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Si tienes alguna pregunta sobre este cambio, no dudes en responder a este correo.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales.<br>
            <span style="font-size:13px; color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function ticketReopenedContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{CUSTOMER_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Te informamos que tu ticket <strong>#{TICKET_NUMBER}</strong> con asunto
            <em>{SUBJECT}</em> ha sido reabierto y nuestro equipo continuará trabajando en él.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280; width:130px;">Número de ticket</td>
                <td style="padding:4px 0; font-size:14px; color:#000; font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Asunto</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{SUBJECT}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Nos pondremos en contacto contigo en cuanto tengamos novedades. Si deseas añadir
            información adicional, puedes responder directamente a este correo.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales.<br>
            <span style="font-size:13px; color:#6b7280;">Equipo de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function newTicketAgentContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Hola <strong>{AGENT_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Se ha creado un nuevo ticket en el sistema que requiere tu atención.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280; width:140px;">Número de ticket</td>
                <td style="padding:4px 0; font-size:14px; color:#000; font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Asunto</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{SUBJECT}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Cliente</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{CUSTOMER_NAME}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Prioridad</td>
                <td style="padding:4px 0; font-size:14px; color:#000; font-weight:bold;">{PRIORITY}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Canal de origen</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{SOURCE}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Por favor, accede al panel de soporte para revisar y gestionar este ticket lo antes posible.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            Saludos cordiales.<br>
            <span style="font-size:13px; color:#6b7280;">Sistema de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function slaEscalationContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#fef2f2; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:12px 16px; font-size:13px; color:#991b1b; font-weight:bold;">
            ESCALACIÓN INTERNA — Este correo es exclusivamente para el equipo de soporte.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            El siguiente ticket ha superado su umbral de SLA y requiere atención inmediata:
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #90bb13; margin-bottom:16px;">
        <tr>
          <td style="padding:14px 16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280; width:150px;">Número de ticket</td>
                <td style="padding:4px 0; font-size:14px; color:#000; font-weight:bold;">#{TICKET_NUMBER}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Asunto</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{SUBJECT}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Cliente</td>
                <td style="padding:4px 0; font-size:14px; color:#000;">{CUSTOMER_NAME}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Fecha límite SLA</td>
                <td style="padding:4px 0; font-size:14px; color:#90bb13; font-weight:bold;">{DUE_AT}</td>
              </tr>
              <tr>
                <td style="padding:4px 0; font-size:13px; color:#6b7280;">Tiempo vencido</td>
                <td style="padding:4px 0; font-size:14px; color:#90bb13; font-weight:bold;">{BREACH_TIME}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 14px 0;">
            Por favor, accede al panel de soporte y gestiona este ticket de forma prioritaria para
            evitar un mayor incumplimiento del acuerdo de nivel de servicio.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:10px 0 0 0;">
            <span style="font-size:13px; color:#6b7280;">Sistema de soporte — {COMPANY_NAME}</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }
}
