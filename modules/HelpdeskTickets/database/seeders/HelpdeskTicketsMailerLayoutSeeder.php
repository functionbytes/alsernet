<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerLayoutLang;

/**
 * Layout compartido para los correos de tickets (creado/reabierto/cambio de
 * estado/SLA/etc.) — NO reutiliza `email_template_wrapper` (el que usa
 * Document): ese layout resuelve sus tags {{ header }}/{{ footer }} contra
 * los alias globales `email_template_header`/`email_template_footer`
 * (MailerTemplateRendererService::renderLayoutTags(), hardcodeados, no
 * configurables por layout), y ese footer es el footer PROMOCIONAL de la
 * tienda (banners de ofertas) — fuera de lugar en un aviso de soporte tipo
 * "tu ticket fue cerrado". Este layout no usa esos tags: header/footer
 * quedan embebidos directo en su propio contenido, así nunca engancha el
 * global.
 */
class HelpdeskTicketsMailerLayoutSeeder extends Seeder
{
    public const ALIAS = 'helpdesk_tickets_wrapper';

    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command?->warn('No languages found - skipping helpdesk tickets mailer layout');

            return;
        }

        // Sin 'name': $fillable del modelo la declara pero la tabla real
        // mailer_layouts no tiene esa columna (desajuste preexistente,
        // confirmado con Schema::getColumnListing()) — no se toca el modelo
        // ni la migración, solo se evita enviarla aquí.
        $layout = MailerLayout::updateOrCreate(
            ['alias' => self::ALIAS],
            [
                'code' => 'helpdesk_tickets_wrapper',
                'type' => 'layout',
                'group_name' => 'helpdesktickets',
                'is_protected' => true,
                'is_enabled' => true,
            ]
        );

        foreach ($langs as $lang) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $layout->id, 'lang_id' => $lang->id],
                ['content' => $this->wrapperContent()]
            );
        }

        $this->command?->info("Layout: {$layout->alias} (ID: {$layout->id})");
    }

    private function wrapperContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center"
       style="margin:0; padding:0; width:100%; background-color:#f5f6f8;">
  <tr>
    <td align="center" valign="top" style="margin:0; padding:0; background-color:#f5f6f8;">

      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6f8;">
        <tr><td height="24" style="line-height:24px; font-size:0;">&nbsp;</td></tr>
      </table>

      <table width="600" cellpadding="0" cellspacing="0" border="0" align="center"
             style="width:600px; max-width:600px; background-color:#FFFFFF; border-radius:8px; overflow:hidden;">
        <tr>
          <td align="left" style="padding:0; margin:0; font-family: Arial, Helvetica, sans-serif;">
            {{ content }}
          </td>
        </tr>
      </table>

      <table width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="width:600px; max-width:600px;">
        <tr>
          <td align="center" style="padding:20px 24px; font-family: Arial, Helvetica, sans-serif; font-size:12px; line-height:1.5; color:#999;">
            Este correo fue enviado por {COMPANY_NAME}. Puedes responder directamente a este mensaje para continuar la conversación sobre tu ticket.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6f8;">
        <tr><td height="24" style="line-height:24px; font-size:0;">&nbsp;</td></tr>
      </table>

    </td>
  </tr>
</table>
HTML;
    }
}
