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
}
