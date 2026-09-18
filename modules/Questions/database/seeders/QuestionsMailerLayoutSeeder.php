<?php

namespace Modules\Questions\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerLayout;
use Modules\Mailer\Models\MailerLayoutLang;

/**
 * Layout compartido por los correos de consultas de producto.
 *
 * No reutiliza `email_template_wrapper` (el de Document) porque ese resuelve sus
 * tags {{ header }}/{{ footer }} contra los alias globales, y ese footer es el
 * promocional de la tienda: fuera de lugar en el acuse de una consulta. Aquí el
 * pie va embebido, así nunca engancha el global.
 */
class QuestionsMailerLayoutSeeder extends Seeder
{
    public const ALIAS = 'questions_wrapper';

    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command?->warn('Sin idiomas disponibles: no se siembra el layout de consultas.');

            return;
        }

        // Sin 'name': el $fillable del modelo la declara pero la tabla real
        // mailer_layouts no tiene esa columna (desajuste preexistente).
        $layout = MailerLayout::updateOrCreate(
            ['alias' => self::ALIAS],
            [
                'code' => 'questions_wrapper',
                'type' => 'layout',
                'group_name' => 'questions',
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
            Este correo lo envía {COMPANY_NAME} porque preguntaste por un producto en nuestra tienda.
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
