<?php

namespace Modules\HelpdeskIntegration\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * Seeds la plantilla del codigo de verificacion de identidad del cliente
 * (gate de "CLIENTE · INTEGRACIONES") en el catalogo del modulo Mailer.
 */
class HelpdeskIntegrationEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command->warn('No languages found - skipping helpdesk integration template translations');

            return;
        }

        $template = MailerTemplate::updateOrCreate(
            ['key' => 'helpdesk_integration.customer_identity_code'],
            [
                'uid' => (string) Str::uuid(),
                'name' => 'Codigo de verificacion de identidad',
                'description' => 'Email enviado al cliente con el codigo OTP para verificar su identidad antes de exponer sus integraciones externas.',
                'module' => 'helpdeskintegration',
                'is_enabled' => true,
                'is_protected' => true,
                'variables' => [
                    ['name' => 'CUSTOMER_NAME', 'required' => true, 'description' => 'Nombre del cliente'],
                    ['name' => 'CODE', 'required' => true, 'description' => 'Codigo de verificacion de 6 digitos'],
                ],
            ]
        );

        foreach ($langs as $lang) {
            MailerTemplateLang::updateOrCreate(
                ['mailer_template_id' => $template->id, 'lang_id' => $lang->id],
                [
                    'subject' => 'Tu código de verificación',
                    'content' => $this->content(),
                ]
            );
        }

        $this->command->info("Template: {$template->name} (ID: {$template->id}) - {$langs->count()} translation(s)");
    }

    private function content(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="background: #fff; border-radius: 8px; padding: 30px; border-top: 4px solid #90bb13;">
        <h2 style="color: #90bb13; margin-top: 0;">Verificación de identidad</h2>

        <p>Hola <strong>{CUSTOMER_NAME}</strong>,</p>

        <p>Un agente de soporte necesita confirmar tu identidad antes de continuar. Usa el siguiente código:</p>

        <div style="background: #f4f4f5; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0;">
            <span style="font-size: 28px; font-weight: 700; letter-spacing: 6px; color: #18181b;">{CODE}</span>
        </div>

        <p style="color: #777; font-size: 13px;">Este código caduca en 10 minutos. Si no solicitaste esta verificación, puedes ignorar este correo.</p>

        <p>Gracias,<br>{COMPANY_NAME}</p>
    </div>
</div>
HTML;
    }
}
