<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rellena contenido real para plantillas que estaban en estado "stub"
 * (contenido de 22-67 caracteres, sin diseño) y añade un header con el
 * logo real de Álvarez a los wrappers de Helpdesk/Questions.
 *
 * NO se reutiliza el contenido de Modules\Mailer\Database\Seeders\HelpdeskTemplatesSeeder
 * (huérfano, nunca se ejecuta desde MailerDatabaseSeeder) porque ese seeder
 * usa el rojo #b10100 de un proyecto ajeno (Caixilharia Blanco, ver
 * ExampleTemplatesSeeder) -- aquí en Álvarez todo va en verde, ver
 * memoria feedback_palette_no_reds_only_greens. El diseño reutiliza el
 * patrón ya verificado en vivo de helpdesk_tickets.ticket_created
 * (banda verde #90bb13 + tabla de datos).
 *
 * Plantillas afectadas:
 * - helpdesk.new_ticket_agent: REAL, usada por NotifyAgentsOnNewTicket
 *   (notificación interna a agentes), body era literalmente "<p>{CUSTOMER_NAME}</p>".
 * - helpdesk.ticket_created: sin uso real detectado en el código actual
 *   (el flujo real de confirmación al cliente usa la clave distinta
 *   helpdesk_tickets.ticket_created, ya con contenido), se completa igual
 *   por consistencia ya que es visible en el listado de plantillas del admin.
 * - helpdesk_integration.customer_identity_code: REAL, código OTP de
 *   verificación de identidad, sin mención de la caducidad de 10 minutos
 *   (CustomerIdentityVerificationService::CODE_TTL_MINUTES).
 *
 * El header (logo) estaba vacío (0 bytes) en email_template_header, así que
 * ningún correo del sistema mostraba el logo Álvarez arriba del contenido
 * (el logo grande sólo vivía enterrado al fondo del footer de marketing,
 * que no se usa en los wrappers transaccionales de Helpdesk/Questions).
 *
 * helpdesk.new_ticket_agent y helpdesk.ticket_created no tenían layout_id
 * (NULL), así que el tag {{ header }} del wrapper nunca les habría aplicado
 * -- se les asigna aquí el wrapper helpdesk_tickets_wrapper (id 4), el mismo
 * que ya usan en vivo los 11 templates reales de las familias
 * helpdesk_tickets.* y helpdesk.* (ticket_reply, ticket_status_changed, etc).
 * helpdesk_integration.customer_identity_code NO se envuelve con ese wrapper
 * porque su pie de página menciona "tu ticket" (no aplica a un código de
 * verificación de identidad); en su lugar el logo se incrusta directo en el
 * contenido de esa plantilla.
 */
return new class extends Migration
{
    public function up(): void
    {
        $wrapperId = DB::table('mailer_layouts')->where('alias', 'helpdesk_tickets_wrapper')->value('id');

        $this->setTemplateContent('helpdesk.new_ticket_agent', $this->newTicketAgentContent(), $wrapperId);
        $this->setTemplateContent('helpdesk.ticket_created', $this->ticketCreatedContent(), $wrapperId);
        $this->setTemplateContent('helpdesk_integration.customer_identity_code', $this->headerContent()."\n".$this->identityCodeContent());

        DB::table('mailer_layout_langs')
            ->whereIn('layout_id', function ($query) {
                $query->select('id')->from('mailer_layouts')->where('alias', 'email_template_header');
            })
            ->update(['content' => $this->headerContent()]);

        $this->addHeaderToWrapper('helpdesk_tickets_wrapper');
        $this->addHeaderToWrapper('questions_wrapper');
    }

    public function down(): void
    {
        $this->setTemplateContent('helpdesk.new_ticket_agent', '<p>{CUSTOMER_NAME}</p>', null);
        $this->setTemplateContent('helpdesk.ticket_created', '<p>{CUSTOMER_NAME}</p>', null);
        $this->setTemplateContent('helpdesk_integration.customer_identity_code', '<p>Hola {CUSTOMER_NAME}, tu codigo es {CODE} — {COMPANY_NAME}</p>');

        DB::table('mailer_layout_langs')
            ->whereIn('layout_id', function ($query) {
                $query->select('id')->from('mailer_layouts')->where('alias', 'email_template_header');
            })
            ->update(['content' => '']);

        $this->removeHeaderFromWrapper('helpdesk_tickets_wrapper');
        $this->removeHeaderFromWrapper('questions_wrapper');
    }

    private function setTemplateContent(string $key, string $content, int|false|null $layoutId = false): void
    {
        $templateId = DB::table('mailer_templates')->where('key', $key)->value('id');

        if (! $templateId) {
            return;
        }

        DB::table('mailer_template_langs')
            ->where('mailer_template_id', $templateId)
            ->update(['content' => $content]);

        // false = no tocar layout_id (caso identity_code, que nunca lo usa);
        // null o un id = actualizarlo explícitamente.
        if ($layoutId !== false) {
            DB::table('mailer_templates')->where('id', $templateId)->update(['layout_id' => $layoutId]);
        }
    }

    private function addHeaderToWrapper(string $alias): void
    {
        $layoutId = DB::table('mailer_layouts')->where('alias', $alias)->value('id');

        if (! $layoutId) {
            return;
        }

        DB::table('mailer_layout_langs')
            ->where('layout_id', $layoutId)
            ->get(['id', 'content'])
            ->each(function ($row) {
                if (! str_contains($row->content ?? '', '{{ content }}') || str_contains($row->content, '{{ header }}')) {
                    return;
                }

                DB::table('mailer_layout_langs')
                    ->where('id', $row->id)
                    ->update(['content' => str_replace('{{ content }}', "{{ header }}\n            {{ content }}", $row->content)]);
            });
    }

    private function removeHeaderFromWrapper(string $alias): void
    {
        $layoutId = DB::table('mailer_layouts')->where('alias', $alias)->value('id');

        if (! $layoutId) {
            return;
        }

        DB::table('mailer_layout_langs')
            ->where('layout_id', $layoutId)
            ->get(['id', 'content'])
            ->each(function ($row) {
                DB::table('mailer_layout_langs')
                    ->where('id', $row->id)
                    ->update(['content' => str_replace("{{ header }}\n            {{ content }}", '{{ content }}', $row->content ?? '')]);
            });
    }

    private function headerContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse;background-color:#ffffff;">
  <tr>
    <td align="center" style="padding:24px 24px 20px;border-bottom:3px solid #90bb13;">
      <a href="https://www.a-alvarez.com" target="_blank" style="text-decoration:none;border:0;">
        <img src="https://imagenes.a-alvarez.com/mailing/Alvarez-logo-es.png"
             width="160"
             alt="Álvarez deporte y tiempo libre"
             style="display:block;border:0;outline:none;text-decoration:none;margin:0 auto;">
      </a>
    </td>
  </tr>
</table>
HTML;
    }

    private function newTicketAgentContent(): string
    {
        return <<<'HTML'
<div style="background: #90bb13; color: #ffffff; padding: 20px 24px;">
    <h2 style="margin: 0; font-size: 18px;">Nuevo ticket asignado</h2>
</div>
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola <strong>{AGENT_NAME}</strong>, se ha creado un nuevo ticket de soporte que requiere atención.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Número de ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{SUBJECT}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Cliente</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{CUSTOMER_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;">Prioridad</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">{PRIORITY}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Canal</td>
            <td style="padding: 8px 0;">{SOURCE}</td>
        </tr>
    </table>
    <p style="color: #666; font-size: 13px; margin: 0;">Accede al panel de soporte para responder a este ticket lo antes posible.</p>
</div>
HTML;
    }

    private function ticketCreatedContent(): string
    {
        return <<<'HTML'
<div style="background: #90bb13; color: #ffffff; padding: 20px 24px;">
    <h2 style="margin: 0; font-size: 18px;">Hemos recibido tu solicitud</h2>
</div>
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>, gracias por contactarnos. Hemos recibido tu solicitud de soporte y nuestro equipo te responderá lo antes posible.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 0 0 16px;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;">Número de ticket</td>
            <td style="padding: 8px 0; border-bottom: 1px solid #eee;">#{TICKET_NUMBER}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">Asunto</td>
            <td style="padding: 8px 0;">{SUBJECT}</td>
        </tr>
    </table>
    <p style="color: #666; font-size: 13px; margin: 0;">Conserva este correo como referencia. Puedes responder directamente a este mensaje para continuar la conversación sobre tu ticket #{TICKET_NUMBER}.</p>
</div>
HTML;
    }

    private function identityCodeContent(): string
    {
        return <<<'HTML'
<div style="background: #90bb13; color: #ffffff; padding: 20px 24px;">
    <h2 style="margin: 0; font-size: 18px;">Código de verificación</h2>
</div>
<div style="padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #333;">
    <p style="margin: 0 0 16px;">Hola <strong>{CUSTOMER_NAME}</strong>, usa el siguiente código para verificar tu identidad:</p>
    <div style="text-align: center; margin: 0 0 20px;">
        <span style="display: inline-block; background: #f7f9f2; border: 1px solid #90bb13; border-radius: 6px; padding: 14px 28px; font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #4f6b0a;">{CODE}</span>
    </div>
    <p style="margin: 0 0 16px; color: #555;">Este código caduca en <strong>10 minutos</strong>. Si no has solicitado este código, puedes ignorar este correo.</p>
    <p style="color: #666; font-size: 13px; margin: 0;">Por seguridad, nunca compartas este código con nadie, ni siquiera con el equipo de soporte de {COMPANY_NAME}.</p>
</div>
HTML;
    }
};
