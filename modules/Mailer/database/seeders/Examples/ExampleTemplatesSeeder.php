<?php

namespace Modules\Mailer\Database\Seeders\Examples;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * ExampleTemplatesSeeder
 *
 * Seeds reference email templates with Caixilharia Blanco branding.
 * Colors: #b10100 accent, #000000 primary.
 * Uses active Locales (locales table) as the language source.
 */
class ExampleTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $locales = Locale::active()->get();

        if ($locales->isEmpty()) {
            $this->command->warn('⚠ No active locales found - skipping template translations');

            return;
        }

        $templates = [
            [
                'key' => 'welcome_email',
                'name' => 'Correo de bienvenida',
                'description' => 'Correo de bienvenida enviado a nuevos usuarios registrados',
                'module' => 'core',
                'subject' => 'Bienvenido a {COMPANY_NAME}',
                'content' => $this->getWelcomeContent(),
            ],
            [
                'key' => 'password_reset',
                'name' => 'Restablecimiento de contraseña',
                'description' => 'Correo enviado cuando un usuario solicita restablecer su contraseña',
                'module' => 'core',
                'subject' => 'Restablece tu contraseña — {COMPANY_NAME}',
                'content' => $this->getPasswordResetContent(),
            ],
            [
                'key' => 'email_verification',
                'name' => 'Verificación de email',
                'description' => 'Correo de verificación de dirección de email',
                'module' => 'core',
                'subject' => 'Verifica tu email — {COMPANY_NAME}',
                'content' => $this->getEmailVerificationContent(),
            ],
            [
                'key' => 'form_notification_admin',
                'name' => 'Notificación de formulario (admin)',
                'description' => 'Notificación al administrador cuando se recibe un nuevo formulario',
                'module' => 'forms',
                'subject' => '[{FORM_NAME}] Nueva respuesta recibida — {SUBMISSION_ID}',
                'content' => $this->getFormNotificationContent(),
            ],
            [
                'key' => 'form_confirmation_client',
                'name' => 'Confirmación de formulario (cliente)',
                'description' => 'Confirmación automática al cliente tras enviar un formulario',
                'module' => 'forms',
                'subject' => 'Hemos recibido tu solicitud — {COMPANY_NAME}',
                'content' => $this->getFormConfirmationContent(),
            ],
        ];

        foreach ($templates as $template) {
            $mailerTemplate = MailerTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'module' => $template['module'],
                    'is_enabled' => true,
                ]
            );

            if (! $mailerTemplate->uid) {
                $mailerTemplate->update(['uid' => (string) Str::uuid()]);
            }

            foreach ($locales as $locale) {
                MailerTemplateLang::updateOrCreate(
                    ['mailer_template_id' => $mailerTemplate->id, 'lang_id' => $locale->id],
                    ['subject' => $template['subject'], 'content' => $template['content']]
                );
            }

            $this->command->info("  ✓ {$template['name']} (ID: {$mailerTemplate->id}) — {$locales->count()} locale(s)");
        }

        $this->command->info('✓ Example templates seeded ('.count($templates).' templates)');
    }

    private function getWelcomeContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{USER_FIRST_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
        <tr>
          <td>
            Nos alegra que te hayas registrado en <strong>{COMPANY_NAME}</strong>.
            Tu cuenta está lista para usar.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #b10100; margin-bottom:24px;">
        <tr>
          <td style="padding:14px 16px;">
            <strong>Accede a tu cuenta para explorar todos los servicios disponibles.</strong>
          </td>
        </tr>
      </table>

      <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
          <td style="background-color:#b10100;border-radius:4px;">
            <a href="{ACCOUNT_DASHBOARD_URL}"
               style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;font-family:Arial,sans-serif;">
              Acceder a mi cuenta
            </a>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="padding:10px 0 0 0;">Saludos cordiales,</td></tr>
        <tr><td style="padding:4px 0 0 0; font-size:13px; color:#6b7280;">{COMPANY_NAME}</td></tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function getPasswordResetContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{USER_FIRST_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
        <tr>
          <td>
            Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en
            <strong>{COMPANY_NAME}</strong>.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #b10100; margin-bottom:24px;">
        <tr>
          <td style="padding:14px 16px;">
            <strong>Este enlace de restablecimiento expira en 60 minutos. Si no realizaste esta solicitud, puedes ignorar este correo.</strong>
          </td>
        </tr>
      </table>

      <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
          <td style="background-color:#b10100;border-radius:4px;">
            <a href="{RESET_PASSWORD_URL}"
               style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;font-family:Arial,sans-serif;">
              Restablecer contraseña
            </a>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="padding:10px 0 0 0;">Saludos cordiales,</td></tr>
        <tr><td style="padding:4px 0 0 0; font-size:13px; color:#6b7280;">{COMPANY_NAME}</td></tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function getEmailVerificationContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family: Arial, Helvetica, sans-serif; color:#000; line-height:1.6; font-size:15px;">
  <tr>
    <td>
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="padding:0 0 12px 0;">Estimado/a <strong>{USER_FIRST_NAME}</strong>,</td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:16px;">
        <tr>
          <td>
            Gracias por registrarte en <strong>{COMPANY_NAME}</strong>.
            Confirma tu dirección de email para activar tu cuenta.
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f6f7f9; border-left:4px solid #b10100; margin-bottom:24px;">
        <tr>
          <td style="padding:14px 16px;">
            <strong>Si no creaste esta cuenta, puedes ignorar este correo con seguridad.</strong>
          </td>
        </tr>
      </table>

      <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
          <td style="background-color:#b10100;border-radius:4px;">
            <a href="{VERIFY_EMAIL_URL}"
               style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;font-family:Arial,sans-serif;">
              Verificar email
            </a>
          </td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td style="padding:10px 0 0 0;">Saludos cordiales,</td></tr>
        <tr><td style="padding:4px 0 0 0; font-size:13px; color:#6b7280;">{COMPANY_NAME}</td></tr>
      </table>
    </td>
  </tr>
</table>
HTML;
    }

    private function getFormNotificationContent(): string
    {
        return <<<'HTML'
<!-- ENCABEZADO DE ALERTA -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;background-color:#b10100;margin-bottom:0;">
  <tr>
    <td style="padding:14px 24px;">
      <p style="margin:0;font-size:11px;font-weight:700;color:#ffffff;font-family:Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;">
        Nueva solicitud recibida
      </p>
    </td>
  </tr>
</table>

<!-- METADATA DEL ENVÍO -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;background-color:#f6f7f9;margin-bottom:0;">
  <tr>
    <td style="padding:16px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr>
          <td style="font-family:Arial,sans-serif;">
            <p style="margin:0 0 4px;font-size:18px;font-weight:700;color:#111827;">
              {FORM_NAME}
            </p>
            <p style="margin:0;font-size:13px;color:#6b7280;">
              Envío&nbsp;<strong style="color:#b10100;">{SUBMISSION_ID}</strong>
              &nbsp;&bull;&nbsp;{SUBMISSION_DATE}
              &nbsp;&bull;&nbsp;IP:&nbsp;{SUBMITTER_IP}
              &nbsp;({SUBMITTER_COUNTRY})
            </p>
          </td>
          <td align="right" valign="top" style="white-space:nowrap;">
            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr>
                <td style="background-color:#b10100;border-radius:4px;">
                  <a href="{ADMIN_URL}"
                     style="display:inline-block;padding:10px 20px;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;font-family:Arial,sans-serif;white-space:nowrap;">
                    Ver solicitud &rarr;
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- LÍNEA SEPARADORA -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
  <tr>
    <td style="border-top:1px solid #e5e7eb;font-size:0;line-height:0;">&nbsp;</td>
  </tr>
</table>

<!-- CAMPOS DEL FORMULARIO -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;font-family:Arial,sans-serif;">
  <tr>
    <td style="padding:20px 24px 4px;">
      <p style="margin:0;font-size:12px;font-weight:700;color:#6b7280;letter-spacing:1px;text-transform:uppercase;">
        Datos enviados por el cliente
      </p>
    </td>
  </tr>
  <tr>
    <td style="padding:8px 24px 24px;">
      {FIELDS_TABLE}
    </td>
  </tr>
</table>

<!-- CTA SECUNDARIO -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;background-color:#f6f7f9;border-top:1px solid #e5e7eb;">
  <tr>
    <td style="padding:16px 24px;">
      <p style="margin:0;font-size:12px;color:#6b7280;font-family:Arial,sans-serif;">
        Para gestionar esta solicitud accede al panel de administración:
        <a href="{ADMIN_URL}" style="color:#b10100;text-decoration:none;font-weight:600;">{ADMIN_URL}</a>
      </p>
    </td>
  </tr>
</table>
HTML;
    }

    private function getFormConfirmationContent(): string
    {
        return <<<'HTML'
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="font-family:Arial,Helvetica,sans-serif;color:#111827;line-height:1.7;font-size:15px;border-collapse:collapse;">
  <tr>
    <td style="padding:0 0 16px;">
      Estimado/a <strong>{USER_FIRST_NAME}</strong>,
    </td>
  </tr>
  <tr>
    <td style="padding:0 0 16px;">
      Hemos recibido correctamente tu solicitud a través del formulario
      <strong>{FORM_NAME}</strong>. Gracias por ponerte en contacto con nosotros.
    </td>
  </tr>
</table>

<!-- CAJA PRÓXIMOS PASOS -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;background-color:#f6f7f9;border-left:4px solid #b10100;margin-bottom:24px;">
  <tr>
    <td style="padding:16px 20px;font-family:Arial,sans-serif;font-size:14px;color:#374151;">
      <p style="margin:0 0 6px;font-weight:700;color:#111827;">Próximos pasos</p>
      <p style="margin:0;">
        Nuestro equipo revisará tu solicitud y se pondrá en contacto contigo
        en un plazo máximo de <strong>24&nbsp;horas</strong> en horario laboral
        (Lun&ndash;Vie,&nbsp;09:00&ndash;17:00).
      </p>
    </td>
  </tr>
</table>

<!-- LÍNEA SEPARADORA -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin-bottom:20px;">
  <tr>
    <td style="border-top:1px solid #e5e7eb;font-size:0;line-height:0;">&nbsp;</td>
  </tr>
</table>

<!-- CONTACTO DIRECTO -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:13px;color:#6b7280;margin-bottom:24px;">
  <tr>
    <td style="padding:0 0 6px;">
      ¿Necesitas contactarnos directamente?
    </td>
  </tr>
  <tr>
    <td>
      <a href="tel:{COMPANY_PHONE}"
         style="color:#b10100;text-decoration:none;font-weight:600;">{COMPANY_PHONE}</a>
      &nbsp;&bull;&nbsp;
      <a href="mailto:{COMPANY_EMAIL}"
         style="color:#b10100;text-decoration:none;">{COMPANY_EMAIL}</a>
    </td>
  </tr>
</table>

<!-- FIRMA -->
<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="border-collapse:collapse;font-family:Arial,sans-serif;">
  <tr>
    <td style="font-size:15px;color:#111827;padding:0 0 2px;">Saludos cordiales,</td>
  </tr>
  <tr>
    <td style="font-size:13px;color:#6b7280;">{COMPANY_NAME}</td>
  </tr>
</table>
HTML;
    }
}
