<?php

namespace Modules\HelpdeskBirthday\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Siembra la plantilla del correo de cumpleaños en el módulo Mailer, para que
 * el diseño se edite desde el admin y no haya que tocar código.
 *
 * Mismo patrón que Mailer\Database\Seeders\HelpdeskTemplatesSeeder.
 */
class BirthdayTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $lang = $this->resolveOrCreateLang();
        $def = $this->definition();

        $template = MailerTemplate::updateOrCreate(
            ['key' => $def['key']],
            [
                'name' => $def['name'],
                'description' => $def['description'],
                'module' => 'helpdesk-birthday',
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

        $this->command?->info("  ✓ {$def['name']} (key: {$def['key']})");
    }

    private function resolveOrCreateLang(): MailerLang
    {
        $lang = MailerLang::where('iso_code', 'es')->first() ?? MailerLang::first();

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

    /**
     * @return array<string, mixed>
     */
    private function definition(): array
    {
        $company = config('app.name', 'Soporte');

        return [
            'key' => 'birthday-coupon',
            'name' => 'Felicitación de cumpleaños con cupón',
            'description' => 'Correo diario a los clientes que cumplen años, con el código de cupón del día y sus fechas de validez.',
            'subject' => '¡Feliz cumpleaños, {CUSTOMER_NAME}! Tu regalo te espera',
            'variables' => [
                ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente', 'category' => 'cliente'],
                ['name' => 'CUSTOMER_EMAIL', 'required' => false, 'description' => 'Email del cliente', 'category' => 'cliente'],
                ['name' => 'COUPON_CODE', 'required' => true, 'description' => 'Código del cupón del día', 'category' => 'cupon'],
                ['name' => 'COUPON_VALID_FROM', 'required' => false, 'description' => 'Inicio de validez (dd/mm/aaaa)', 'category' => 'cupon'],
                ['name' => 'COUPON_VALID_TO', 'required' => false, 'description' => 'Fin de validez (dd/mm/aaaa)', 'category' => 'cupon'],
                ['name' => 'COUPON_AMOUNT', 'required' => false, 'description' => 'Importe del cupón', 'category' => 'cupon'],
                ['name' => 'COUPON_MIN_PURCHASE', 'required' => false, 'description' => 'Compra mínima para poder usarlo', 'category' => 'cupon'],
                ['name' => 'SHOP_URL', 'required' => false, 'description' => 'Enlace a la tienda del botón principal', 'category' => 'sistema'],
                ['name' => 'UNSUBSCRIBE_URL', 'required' => true, 'description' => 'Enlace de baja de estos correos', 'category' => 'sistema'],
            ],
            'content' => $this->content($company),
        ];
    }

    /**
     * HTML de correo, no HTML de web.
     *
     * Va con tablas y estilos inline a propósito: Outlook no soporta flex ni
     * grid, y Gmail descarta el <style> del <head> en buena parte de sus
     * clientes. Es justo lo contrario de la norma del panel (donde los estilos
     * inline están prohibidos), porque aquí el destinatario es un cliente de
     * correo ajeno y no nuestro navegador.
     *
     * Ancho fijo de 600px: el estándar que entra sin scroll horizontal en
     * prácticamente todos los clientes de escritorio y móvil.
     */
    private function content(string $company): string
    {
        $green = '#90bb13';
        $dark = '#4f6b0a';
        $ink = '#2f3640';
        $muted = '#7a8290';
        $border = '#e4e7ec';

        return <<<HTML
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f5f6f7;margin:0;padding:24px 0;">
            <tr>
                <td align="center" style="padding:0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px;max-width:100%;background-color:#ffffff;border-radius:12px;overflow:hidden;font-family:Helvetica,Arial,sans-serif;">

                        <tr>
                            <td align="center" style="background-color:{$green};padding:32px 24px;">
                                <div style="font-size:40px;line-height:40px;">&#127874;</div>
                                <h1 style="margin:12px 0 0;font-size:26px;line-height:32px;color:#ffffff;font-weight:bold;">
                                    &iexcl;Feliz cumplea&ntilde;os, {CUSTOMER_NAME}!
                                </h1>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:28px 32px 8px;font-size:16px;line-height:24px;color:{$ink};">
                                <p style="margin:0 0 20px;">
                                    En {$company} queremos celebrarlo contigo, as&iacute; que te
                                    dejamos un regalo para que te des un capricho.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:0 32px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:2px dashed {$green};border-radius:10px;background-color:#f7fbe9;">
                                    <tr>
                                        <td align="center" style="padding:22px 16px;">
                                            <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:{$muted};">Tu c&oacute;digo</div>
                                            <div style="margin-top:8px;font-family:'Courier New',Courier,monospace;font-size:26px;font-weight:bold;letter-spacing:2px;color:{$dark};">
                                                {COUPON_CODE}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:20px 32px 0;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:14px;line-height:22px;color:{$ink};">
                                    <tr>
                                        <td style="padding:6px 0;border-bottom:1px solid {$border};color:{$muted};">V&aacute;lido desde</td>
                                        <td align="right" style="padding:6px 0;border-bottom:1px solid {$border};">{COUPON_VALID_FROM}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;border-bottom:1px solid {$border};color:{$muted};">V&aacute;lido hasta</td>
                                        <td align="right" style="padding:6px 0;border-bottom:1px solid {$border};"><strong>{COUPON_VALID_TO}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;border-bottom:1px solid {$border};color:{$muted};">Importe</td>
                                        <td align="right" style="padding:6px 0;border-bottom:1px solid {$border};">{COUPON_AMOUNT} &euro;</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:{$muted};">Compra m&iacute;nima</td>
                                        <td align="right" style="padding:6px 0;">{COUPON_MIN_PURCHASE} &euro;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td align="center" style="padding:28px 32px 8px;">
                                <!-- VML para que el bot&oacute;n tenga fondo tambi&eacute;n en Outlook de escritorio. -->
                                <!--[if mso]>
                                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{SHOP_URL}" style="height:48px;v-text-anchor:middle;width:260px;" arcsize="20%" strokecolor="{$green}" fillcolor="{$green}">
                                <w:anchorlock/><center style="color:#ffffff;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:bold;">Usar mi regalo</center>
                                </v:roundrect>
                                <![endif]-->
                                <!--[if !mso]><!-- -->
                                <a href="{SHOP_URL}" style="display:inline-block;background-color:{$green};color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;padding:14px 34px;border-radius:8px;">
                                    Usar mi regalo
                                </a>
                                <!--<![endif]-->
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:20px 32px 28px;font-size:12px;line-height:20px;color:{$muted};text-align:center;">
                                <p style="margin:0 0 10px;">
                                    Introduce el c&oacute;digo en la cesta antes de finalizar tu pedido.
                                </p>
                                <p style="margin:0;">
                                    Si no quieres recibir m&aacute;s felicitaciones,
                                    <a href="{UNSUBSCRIBE_URL}" style="color:{$muted};text-decoration:underline;">date de baja aqu&iacute;</a>.
                                </p>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
        HTML;
    }
}
