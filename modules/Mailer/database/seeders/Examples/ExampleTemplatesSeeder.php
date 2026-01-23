<?php

namespace Modules\Mailer\Database\Seeders\Examples;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

/**
 * ExampleTemplatesSeeder
 *
 * Seeds example email templates to provide templates developers/admins can use
 * or use as references for creating their own templates.
 */
class ExampleTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all available languages
        $langs = \App\Models\Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command->warn('⚠ No languages found - skipping template translations');

            return;
        }

        $templates = [
            [
                'key' => 'welcome_email',
                'name' => 'Correo de Bienvenida',
                'description' => 'Correo de bienvenida enviado a nuevos usuarios',
                'module' => 'core',
                'subject' => 'Bienvenido a {COMPANY_NAME}',
                'content' => '<h1>¡Bienvenido!</h1><p>Estimado/a {CUSTOMER_NAME},</p><p>Nos complace darte la bienvenida a {COMPANY_NAME}.</p>',
            ],
            [
                'key' => 'password_reset',
                'name' => 'Restablecimiento de Contraseña',
                'description' => 'Correo enviado cuando un usuario solicita restablecer su contraseña',
                'module' => 'core',
                'subject' => 'Restablecer tu contraseña',
                'content' => '<h1>Restablecer contraseña</h1><p>Estimado/a {CUSTOMER_NAME},</p><p>Has solicitado restablecer tu contraseña.</p>',
            ],
            [
                'key' => 'email_verification',
                'name' => 'Verificación de Email',
                'description' => 'Correo de verificación de dirección de email',
                'module' => 'core',
                'subject' => 'Verifica tu dirección de email',
                'content' => '<h1>Verifica tu email</h1><p>Estimado/a {CUSTOMER_NAME},</p><p>Por favor verifica tu dirección de email.</p>',
            ],
        ];

        foreach ($templates as $template) {
            $mailerTemplate = MailerTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'uid' => (string) Str::ulid(),
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'module' => $template['module'],
                    'is_enabled' => true,
                ]
            );

            // Create translations for ALL languages
            foreach ($langs as $lang) {
                MailerTemplateLang::updateOrCreate(
                    ['mailer_template_id' => $mailerTemplate->id, 'lang_id' => $lang->id],
                    [
                        'subject' => $template['subject'],
                        'content' => $template['content'],
                    ]
                );
            }

            $this->command->info("✓ Example template: {$template['name']} (ID: {$mailerTemplate->id}) - {$langs->count()} translation(s)");
        }

        $this->command->info('✓ All example templates seeded successfully ('.count($templates).' templates)');
    }
}
