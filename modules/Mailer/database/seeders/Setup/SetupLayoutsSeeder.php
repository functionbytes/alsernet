<?php

namespace Modules\Mailer\Database\Seeders\Setup;

use Illuminate\Database\Seeder;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerLayoutLang;

/**
 * SetupLayoutsSeeder
 *
 * Seeds the base email layout components (header, footer, wrapper).
 * These are foundational layouts used by all email templates.
 */
class SetupLayoutsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all available languages
        $langs = \App\Models\Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command->warn('⚠ No languages found - skipping layout translations');

            return;
        }

        // HEADER Layout
        $headerLayout = MailerLayout::updateOrCreate(
            ['alias' => 'email_template_header'],
            [
                'group_name' => 'mail_templates',
                'code' => 'email_header',
                'type' => 'partial',
                'is_protected' => true,
                'is_enabled' => true,
            ]
        );

        // Create translations for ALL languages
        foreach ($langs as $lang) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $headerLayout->id, 'lang_id' => $lang->id],
                [
                    'subject' => 'Encabezado de plantilla de correo',
                    'content' => $this->getHeaderContent(),
                ]
            );
        }

        // FOOTER Layout
        $footerLayout = MailerLayout::updateOrCreate(
            ['alias' => 'email_template_footer'],
            [
                'group_name' => 'mail_templates',
                'code' => 'email_footer',
                'type' => 'partial',
                'is_protected' => true,
                'is_enabled' => true,
            ]
        );

        // Create translations for ALL languages
        foreach ($langs as $lang) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $footerLayout->id, 'lang_id' => $lang->id],
                [
                    'subject' => 'Pie de página de plantilla de correo',
                    'content' => $this->getFooterContent(),
                ]
            );
        }

        // WRAPPER Layout (complete layout)
        $wrapperLayout = MailerLayout::updateOrCreate(
            ['alias' => 'email_template_wrapper'],
            [
                'group_name' => 'mail_templates',
                'code' => 'email_wrapper',
                'type' => 'layout',
                'is_protected' => true,
                'is_enabled' => true,
            ]
        );

        // Create translations for ALL languages
        foreach ($langs as $lang) {
            MailerLayoutLang::updateOrCreate(
                ['layout_id' => $wrapperLayout->id, 'lang_id' => $lang->id],
                [
                    'subject' => 'Plantilla completa de correo',
                    'content' => $this->getWrapperContent(),
                ]
            );
        }

        $this->command->info('✓ Base email layouts seeded:');
        $this->command->info("  - Header (ID: {$headerLayout->id})");
        $this->command->info("  - Footer (ID: {$footerLayout->id})");
        $this->command->info("  - Wrapper (ID: {$wrapperLayout->id})");
        $this->command->info("  ✓ Translations created for {$langs->count()} language(s)");
    }

    private function getHeaderContent(): string
    {
        return '';
    }

    private function getFooterContent(): string
    {
        return <<<'HTML'
<!-- =========================
     FOOTER (EMAIL-SAFE)
========================= -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" align="center">
  <tr>
    <td align="center" style="padding:0;margin:0;">

      <table width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="width:600px; max-width:600px;">
        <tr class="m-footer-row" width="600" style="padding-bottom:40px !important;">
          <td class="m-outer" align="center">
            <table class="m-wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
              <tbody>

                <!-- LOGO (RESPONSIVE SIN <style>) -->
                <tr>
                  <td class="m-center m-pt30" align="center" style="padding-top:30px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="center">
                          <a class="m-link"
                             href="{HOME_URL}"
                             target="_blank"
                             style="text-decoration:none; border:0;">
                            <img class="m-img m-img-logo"
                                 alt="Alvarez deporte y tiempo libre"
                                 src="https://imagenes.a-alvarez.com/mailing/Alvarez-logo-es.png"
                                 width="400"
                                 border="0"
                                 style="display:block; width:100%; max-width:400px; height:auto; border:0; outline:none; text-decoration:none;">
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- TITULO + ICONOS -->
                <tr>
                  <td class="m-center m-py27 m-px20" align="center" style="padding:27px 20px;">
                    <table class="m-mini" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td class="m-mini-td-r" align="right" style="vertical-align:middle;">
                          <img class="m-img m-img-31"
                               alt="tiendas alvarez deporte y tiempo libre"
                               src="https://imagenes.a-alvarez.com/mailing/footer/Cierre-Oferta-express-direccion-cuadrado.jpg"
                               width="31"
                               border="0"
                               style="display:block; width:31px; height:auto; border:0; outline:none;">
                        </td>
                        <td class="m-mini-td-c" align="center" style="padding:0 10px; vertical-align:middle;">
                          <div class="m-title" style="font-weight:bold; line-height:1.2;">
                            Compromiso del mejor precio
                          </div>
                        </td>
                        <td class="m-mini-td-l" align="left" style="vertical-align:middle;">
                          <img class="m-img m-img-31"
                               alt="tiendas alvarez deporte y tiempo libre"
                               src="https://imagenes.a-alvarez.com/mailing/footer/Cierre-Oferta-express-direccion-cuadrado.jpg"
                               width="31"
                               border="0"
                               style="display:block; width:31px; height:auto; border:0; outline:none;">
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- BANDA DEPORTES (RESPONSIVE) -->
                <tr>
                  <td class="m-center m-p0" align="center" style="padding:0;">
                    <img class="m-img m-img-600"
                         alt="deportes alvarez deporte y tiempo libre"
                         src="https://imagenes.a-alvarez.com/mailing/footer/Cierre-Oferta-express-banda_deportes-1200-es.jpg"
                         width="600"
                         border="0"
                         style="display:block; width:100%; max-width:600px; height:auto; border:0; outline:none;">
                  </td>
                </tr>

                <!-- WEB (RESPONSIVE) -->
                <tr>
                  <td class="m-center m-p0" align="center" style="padding:0;">
                    <a class="m-link" href="{HOME_URL}" target="_blank" style="text-decoration:none; border:0;">
                      <img class="m-img m-img-600"
                           alt="web alvarez deporte y tiempo libre"
                           src="https://imagenes.a-alvarez.com/mailing/footer/Cierre-Oferta-express-contacto_web-es.jpg"
                           width="600"
                           border="0"
                           style="display:block; width:100%; max-width:600px; height:auto; border:0; outline:none;">
                    </a>
                  </td>
                </tr>

                <!-- DIRECCIÓN / TIENDAS (RESPONSIVE) -->
                <tr>
                  <td class="m-center m-p0" align="center" style="padding:0;">
                    <a class="m-link" href="{STORES_URL}" target="_blank" style="text-decoration:none; border:0;">
                      <img class="m-img m-img-600"
                           alt="tiendas alvarez deporte y tiempo libre"
                           src="https://imagenes.a-alvarez.com/mailing/footer/Cierre-Oferta-express-direccion-es.jpg"
                           width="600"
                           border="0"
                           style="display:block; width:100%; max-width:600px; height:auto; border:0; outline:none;">
                    </a>
                  </td>
                </tr>

                <!-- ESPACIO -->
                <tr>
                  <td class="m-py15" style="padding:15px 0; line-height:15px; font-size:0;">&nbsp;</td>
                </tr>

                <!-- LÍNEA (RESPONSIVE) -->
                <tr>
                  <td class="m-center m-p0" align="center" style="padding:0;">
                    <img class="m-img m-img-600"
                         alt=""
                         src="https://imagenes.a-alvarez.com/mailing/linea-gris.png"
                         width="600"
                         border="0"
                         style="display:block; width:100%; max-width:600px; height:auto; border:0; outline:none;">
                  </td>
                </tr>

                <!-- ESPACIO -->
                <tr>
                  <td class="m-py10sp" style="padding:10px 0; line-height:10px; font-size:0;">&nbsp;</td>
                </tr>

                <!-- APP + BADGES -->
                <tr>
                  <td class="m-center m-py10" align="center" style="padding:10px 0;">
                    <table class="m-app-table" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
                      <tr>
                        <td class="m-col-200 m-center m-app-text" width="200" align="center" style="width:200px; vertical-align:middle;">
                          <div class="m-app-title" style="font-weight:bold; line-height:1.2;">Alvarez en tu bolsillo</div>
                          <div class="m-app-sub" style="line-height:1.2;">Descarga nuestra app</div>
                        </td>

                        <td class="m-col-200 m-center" width="200" align="center" style="width:200px; vertical-align:middle;">
                          <a class="m-link" href="{ANDROID_APP_URL}" target="_blank" style="text-decoration:none; border:0;">
                            <img class="m-img m-img-h50"
                                 alt="alvarez deporte y tiempo libre en Play Store"
                                 height="50"
                                 border="0"
                                 src="https://imagenes.a-alvarez.com/mailing/APP-MOVIL-google-play-badge-es.png"
                                 style="display:block; height:50px; width:auto; border:0; outline:none;">
                          </a>
                        </td>

                        <td class="m-col-200 m-center" width="200" align="center" style="width:200px; vertical-align:middle;">
                          <a class="m-link" href="{IOS_APP_URL}" target="_blank" style="text-decoration:none; border:0;">
                            <img class="m-img m-img-h50"
                                 alt="alvarez deporte y tiempo libre en App Store"
                                 height="50"
                                 border="0"
                                 src="https://imagenes.a-alvarez.com/mailing/APP-MOVIL-app-store-black-badge-es.png"
                                 style="display:block; height:50px; width:auto; border:0; outline:none;">
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- ESPACIO -->
                <tr>
                  <td class="m-py10sp" style="padding:10px 0; line-height:10px; font-size:0;">&nbsp;</td>
                </tr>

              </tbody>
            </table>
          </td>
        </tr>
      </table>

    </td>
  </tr>
</table>
HTML;
    }

    private function getWrapperContent(): string
    {
        return <<<'HTML'
                {{ header }}
                {{ content }}
                {{ footer }}
                HTML;
    }
}
