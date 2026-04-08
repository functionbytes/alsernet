<?php

namespace Modules\Backup\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

class BackupEmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command->warn('No languages found - skipping backup template translations');

            return;
        }

        foreach ($this->getTemplates() as $template) {
            $mailerTemplate = MailerTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'uid' => (string) Str::ulid(),
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'module' => 'backup',
                    'is_enabled' => true,
                    'is_protected' => false,
                ]
            );

            foreach ($langs as $lang) {
                MailerTemplateLang::updateOrCreate(
                    ['mailer_template_id' => $mailerTemplate->id, 'lang_id' => $lang->id],
                    [
                        'subject' => $template['subject'],
                        'content' => $template['content'],
                    ]
                );
            }

            $this->command->info("Template: {$template['name']} (ID: {$mailerTemplate->id})");
        }

        $this->command->info('Backup email templates seeded ('.count($this->getTemplates()).' templates)');
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, subject: string, content: string}>
     */
    private function getTemplates(): array
    {
        return [
            [
                'key' => 'BACKUP_SUCCESS',
                'name' => 'Backup completado',
                'description' => 'Email enviado cuando un backup se completa exitosamente.',
                'subject' => 'Backup completado: {APP_NAME}',
                'content' => $this->successTemplate(),
            ],
            [
                'key' => 'BACKUP_FAILED',
                'name' => 'Backup fallido',
                'description' => 'Email enviado cuando un backup falla.',
                'subject' => 'Error en backup: {APP_NAME}',
                'content' => $this->failedTemplate(),
            ],
        ];
    }

    private function successTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <h1 style="color: #13C672; font-size: 24px; margin-bottom: 10px;">Backup completado exitosamente</h1>

    <p>El backup de <strong>{APP_NAME}</strong> se ha completado correctamente.</p>

    <div style="background: #d4edda; border-left: 4px solid #13C672; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0 0 4px 0; font-size: 16px; font-weight: bold; color: #333;">Backup exitoso</p>
        <p style="margin: 0; font-size: 14px; color: #555;">Todos los datos han sido respaldados correctamente</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalles del backup</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 40%;">Aplicación:</td>
            <td style="padding: 8px 0; font-weight: bold;">{APP_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Tamaño:</td>
            <td style="padding: 8px 0; font-weight: bold;">{BACKUP_SIZE}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Fecha y hora:</td>
            <td style="padding: 8px 0; font-weight: bold;">{BACKUP_DATE}</td>
        </tr>
    </table>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{BACKUP_URL}" style="display: inline-block; background: #13C672; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Ver backups</a>
    </div>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p style="font-size: 12px; color: #999;">Este es un correo automático del sistema de copias de seguridad. Por favor no respondas a este mensaje.</p>

    <p style="margin-top: 16px;">Saludos,<br><strong>{APP_NAME}</strong></p>
</div>
HTML;
    }

    private function failedTemplate(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
    <h1 style="color: #FA896B; font-size: 24px; margin-bottom: 10px;">Error en el backup</h1>

    <p>El backup de <strong>{APP_NAME}</strong> ha fallado. Se requiere tu atención.</p>

    <div style="background: #fde8e3; border-left: 4px solid #FA896B; padding: 16px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0 0 4px 0; font-size: 16px; font-weight: bold; color: #333;">Backup fallido</p>
        <p style="margin: 0; font-size: 14px; color: #555;">El proceso de backup no se completó correctamente</p>
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px;">Detalles del error</h2>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tr>
            <td style="padding: 8px 0; color: #777; width: 40%;">Aplicación:</td>
            <td style="padding: 8px 0; font-weight: bold;">{APP_NAME}</td>
        </tr>
        <tr>
            <td style="padding: 8px 0; color: #777;">Fecha y hora:</td>
            <td style="padding: 8px 0; font-weight: bold;">{BACKUP_DATE}</td>
        </tr>
    </table>

    <h3 style="font-size: 16px; color: #555;">Mensaje de error</h3>
    <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 16px; border-radius: 4px; font-family: monospace; font-size: 13px; color: #555; word-break: break-word;">
        {ERROR_MESSAGE}
    </div>

    <h2 style="font-size: 18px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 24px;">Acciones recomendadas</h2>

    <ul style="font-size: 14px; color: #555; padding-left: 20px;">
        <li class="mb-2">Revisa los logs del sistema para obtener más detalles</li>
        <li class="mb-2">Verifica que haya espacio suficiente en disco</li>
        <li class="mb-2">Comprueba que las credenciales de base de datos sean correctas</li>
        <li>Intenta crear un backup manual para diagnosticar el problema</li>
    </ul>

    <div style="text-align: center; margin: 24px 0;">
        <a href="{BACKUP_URL}" style="display: inline-block; background: #FA896B; color: #fff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px;">Ver backups</a>
    </div>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">

    <p style="font-size: 12px; color: #999;">Este es un correo automático del sistema de copias de seguridad. Por favor no respondas a este mensaje.</p>

    <p style="margin-top: 16px;">Saludos,<br><strong>{APP_NAME}</strong></p>
</div>
HTML;
    }
}
