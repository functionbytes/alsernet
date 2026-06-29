<?php

namespace Modules\Mailer\Database\Seeders\Setup;

use Illuminate\Database\Seeder;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerLayoutLang;

/**
 * SetupLayoutsSeeder
 *
 * Seeds base email layout components (header, footer, wrapper).
 * Uses active Locales (locales table) as the language source.
 * Colors: #90bb13 accent, #000000 primary, white background.
 */
class SetupLayoutsSeeder extends Seeder
{
    public function run(): void
    {
        $locales = Locale::active()->get();

        if ($locales->isEmpty()) {
            $this->command->warn('⚠ No active locales found - skipping layout translations');

            return;
        }

        // HEADER
        $headerLayout = MailerLayout::updateOrCreate(
            ['alias' => 'email_template_header'],
            ['group_name' => 'mail_templates', 'code' => 'email_header', 'type' => 'partial', 'is_protected' => true, 'is_enabled' => true]
        );

        foreach ($locales as $locale) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $headerLayout->id, 'lang_id' => $locale->id],
                ['subject' => 'Encabezado de plantilla de correo', 'content' => $this->getHeaderContent()]
            );
        }

        // FOOTER
        $footerLayout = MailerLayout::updateOrCreate(
            ['alias' => 'email_template_footer'],
            ['group_name' => 'mail_templates', 'code' => 'email_footer', 'type' => 'partial', 'is_protected' => true, 'is_enabled' => true]
        );

        foreach ($locales as $locale) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $footerLayout->id, 'lang_id' => $locale->id],
                ['subject' => 'Pie de página de plantilla de correo', 'content' => $this->getFooterContent()]
            );
        }

        // WRAPPER
        $wrapperLayout = MailerLayout::updateOrCreate(
            ['alias' => 'email_template_wrapper'],
            ['group_name' => 'mail_templates', 'code' => 'email_wrapper', 'type' => 'layout', 'is_protected' => true, 'is_enabled' => true]
        );

        foreach ($locales as $locale) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $wrapperLayout->id, 'lang_id' => $locale->id],
                ['subject' => 'Plantilla completa de correo', 'content' => $this->getWrapperContent()]
            );
        }

        $this->command->info("✓ Base layouts seeded (header, footer, wrapper) — {$locales->count()} locale(s)");
    }

    private function getHeaderContent(): string
    {
        return '';
    }

    private function getFooterContent(): string
    {
        $logoUrl = url('media/0/fnL5XDsB9dREbPHjAk5PzhDQd4o8btomFgYosNbA.png');
        $siteUrl = url('/');

        return <<<HTML
<!-- ===== FOOTER ===== -->
<table width="600" cellpadding="0" cellspacing="0" border="0" align="center"
       style="width:600px;max-width:600px;border-collapse:collapse;">
  <tbody>

    <!-- LÍNEA GRIS SEPARADORA -->
    <tr>
      <td style="border-top:1px solid #e5e7eb;font-size:0;line-height:0;height:1px;">&nbsp;</td>
    </tr>

    <!-- LOGO CENTRADO -->
    <tr>
      <td align="center" style="background-color:#ffffff;padding:28px 40px 16px;">
        <a href="{SITE_URL}" target="_blank" style="text-decoration:none;border:0;">
          <img src="{$logoUrl}"
               width="200"
               alt="Caixilharia Blanco"
               style="display:block;border:0;outline:none;text-decoration:none;width:200px;height:auto;margin:0 auto;">
        </a>
      </td>
    </tr>

    <!-- BANDA DE SERVICIOS (rojo) -->
    <tr>
      <td align="center" style="background-color:#90bb13;padding:12px 24px;">
        <p style="margin:0;font-size:10px;font-weight:700;color:#ffffff;font-family:Arial,sans-serif;letter-spacing:0.5px;line-height:1.8;text-align:center;">
          VENTANAS PVC &nbsp;&bull;&nbsp; ESTORES EXTERIORES &nbsp;&bull;&nbsp; MOSQUITERAS &nbsp;&bull;&nbsp; BARANDILLAS DE VIDRIO
        </p>
      </td>
    </tr>

    <!-- CONTACTO: WEB Y EMAIL (50/50 centrado) -->
    <tr>
      <td style="background-color:#f6f7f9;padding:0;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
          <tr>
            <td width="300" align="center" style="padding:14px 20px;border-right:1px solid #d1d5db;text-align:center;">
              <a href="{SITE_URL}" target="_blank"
                 style="font-size:13px;color:#374151;font-family:Arial,sans-serif;text-decoration:none;">
                www.caixilhariablanco.pt
              </a>
            </td>
            <td width="300" align="center" style="padding:14px 20px;text-align:center;">
              <a href="mailto:info@caixilhariablanco.pt"
                 style="font-size:13px;color:#374151;font-family:Arial,sans-serif;text-decoration:none;">
                info@caixilhariablanco.pt
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- DIRECCIÓN + TELÉFONO + HORARIO EN 3 COLUMNAS -->
    <tr>
      <td align="center" style="background-color:#ffffff;padding:20px 40px 24px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
          <tr>

            <!-- DIRECCIÓN -->
            <td width="40%" valign="top" align="center"
                style="padding:0 8px;border-right:1px solid #e5e7eb;">
              <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#90bb13;font-family:Arial,sans-serif;text-align:center;">
                Dirección
              </p>
              <p style="margin:0;font-size:12px;color:#374151;font-family:Arial,sans-serif;line-height:1.6;text-align:center;">
                Estrada Nacional 8 N12<br>
                Alcobaça, Leiria<br>
                2460-349, Portugal
              </p>
            </td>

            <!-- TELÉFONO -->
            <td width="30%" valign="top" align="center"
                style="padding:0 8px;border-right:1px solid #e5e7eb;">
              <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#90bb13;font-family:Arial,sans-serif;text-align:center;">
                Teléfono
              </p>
              <p style="margin:0;font-size:12px;font-family:Arial,sans-serif;text-align:center;">
                <a href="tel:+351913893333"
                   style="color:#374151;text-decoration:none;line-height:1.6;">
                  +351 913 893 333
                </a>
              </p>
            </td>

            <!-- HORARIO -->
            <td width="30%" valign="top" align="center" style="padding:0 8px;">
              <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#90bb13;font-family:Arial,sans-serif;text-align:center;">
                Horario
              </p>
              <p style="margin:0;font-size:12px;color:#374151;font-family:Arial,sans-serif;line-height:1.6;text-align:center;">
                Lun&ndash;Vie<br>
                09:00&ndash;17:00
              </p>
            </td>

          </tr>
        </table>
      </td>
    </tr>

    <!-- COPYRIGHT -->
    <tr>
      <td align="center" style="background-color:#90bb13;padding:14px 40px;">
        <p style="margin:0;font-size:11px;color:#ffffff;font-family:Arial,sans-serif;text-align:center;">
          &copy; {CURRENT_YEAR} Caixilharia Blanco. Todos los derechos reservados.
        </p>
      </td>
    </tr>

  </tbody>
</table>
<!-- ===== /FOOTER ===== -->
HTML;
    }

    private function getWrapperContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center"
       style="margin:0; padding:0; width:100%; background-color:#f5f6f8;">
  <tr>
    <td align="center" valign="top" style="margin:0; padding:0; background-color:#f5f6f8;">

      <!-- ESPACIO SUPERIOR -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6f8;">
        <tr>
          <td height="16" style="line-height:16px; font-size:0;">&nbsp;</td>
        </tr>
      </table>

      <!-- CONTENEDOR PRINCIPAL -->
      <table width="600" cellpadding="0" cellspacing="0" border="0" align="center"
             style="width:600px; max-width:600px; background-color:#FFFFFF;">
        <tr>
          <td align="left" style="padding:0; margin:0;">

            {{ header }}

            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFFFFF;">
              <tr>
                <td align="left"
                    style="padding:24px 24px 0 24px; font-family: Arial, Helvetica, sans-serif;">
                  {{ content }}
                </td>
              </tr>
              <tr>
                <td height="24" style="line-height:24px; font-size:0; padding:0 24px;">&nbsp;</td>
              </tr>
            </table>

            {{ footer }}

          </td>
        </tr>
      </table>

      <!-- ESPACIO INFERIOR -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6f8;">
        <tr>
          <td height="16" style="line-height:16px; font-size:0;">&nbsp;</td>
        </tr>
      </table>

    </td>
  </tr>
</table>
HTML;
    }
}
