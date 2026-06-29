<?php

namespace Modules\Mailer\Database\Seeders\Setup;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Locales\Models\Locale;
use Modules\Mailer\Models\MailerVariable;
use Modules\Mailer\Models\MailerVariableLang;

/**
 * SetupVariablesSeeder
 *
 * Seeds the core email template variables that can be used in all templates.
 * Uses active Locales (locales table) as the language source.
 */
class SetupVariablesSeeder extends Seeder
{
    public function run(): void
    {
        $locales = Locale::active()->get();

        if ($locales->isEmpty()) {
            $this->command->warn('⚠ No active locales found - skipping variable translations');

            return;
        }

        $variables = [
            // USER
            ['name' => 'USER_NAME',       'description' => 'Nombre completo del usuario',          'example_value' => 'Juan García López', 'category' => 'user',    'is_system' => true],
            ['name' => 'USER_EMAIL',      'description' => 'Email del usuario',                    'example_value' => 'juan@example.com',  'category' => 'user',    'is_system' => true],
            ['name' => 'USER_FIRST_NAME', 'description' => 'Primer nombre del usuario',            'example_value' => 'Juan',              'category' => 'user',    'is_system' => true],
            ['name' => 'USER_LAST_NAME',  'description' => 'Apellido del usuario',                 'example_value' => 'García',            'category' => 'user',    'is_system' => true],
            ['name' => 'USER_PHONE',      'description' => 'Teléfono del usuario',                 'example_value' => '+34 666 123 456',   'category' => 'user',    'is_system' => true],

            // SITE
            ['name' => 'SITE_NAME',       'description' => 'Nombre del sitio web',                 'example_value' => 'Mi Sitio Web',                   'category' => 'site', 'is_system' => true],
            ['name' => 'SITE_URL',        'description' => 'URL principal del sitio',              'example_value' => 'https://www.example.com',        'category' => 'site', 'is_system' => true],
            ['name' => 'SITE_LOGO_URL',   'description' => 'URL del logo del sitio',               'example_value' => 'https://www.example.com/logo.png', 'category' => 'site', 'is_system' => true],
            ['name' => 'SUPPORT_EMAIL',   'description' => 'Email de soporte técnico',             'example_value' => 'soporte@example.com',            'category' => 'site', 'is_system' => true],
            ['name' => 'SUPPORT_PHONE',   'description' => 'Teléfono de soporte',                  'example_value' => '+34 900 123 456',                'category' => 'site', 'is_system' => true],

            // COMPANY
            ['name' => 'COMPANY_NAME',        'description' => 'Nombre legal de la empresa',      'example_value' => 'Mi Empresa, S.L.',         'category' => 'company', 'is_system' => true],
            ['name' => 'COMPANY_ADDRESS',     'description' => 'Dirección de la empresa',         'example_value' => 'Calle Principal 123',       'category' => 'company', 'is_system' => true],
            ['name' => 'COMPANY_CITY',        'description' => 'Ciudad de la empresa',            'example_value' => 'Madrid',                   'category' => 'company', 'is_system' => true],
            ['name' => 'COMPANY_POSTAL_CODE', 'description' => 'Código postal de la empresa',     'example_value' => '28001',                    'category' => 'company', 'is_system' => true],
            ['name' => 'COMPANY_COUNTRY',     'description' => 'País de la empresa',              'example_value' => 'España',                   'category' => 'company', 'is_system' => true],
            ['name' => 'COMPANY_PHONE',       'description' => 'Teléfono de la empresa',          'example_value' => '+34 91 123 45 67',         'category' => 'company', 'is_system' => true],
            ['name' => 'COMPANY_EMAIL',       'description' => 'Email corporativo',               'example_value' => 'info@example.com',         'category' => 'company', 'is_system' => true],

            // DATE
            ['name' => 'CURRENT_DATE',  'description' => 'Fecha actual formateada', 'example_value' => '01/01/2026', 'category' => 'date', 'is_system' => true],
            ['name' => 'CURRENT_YEAR',  'description' => 'Año actual',              'example_value' => '2026',       'category' => 'date', 'is_system' => true],
            ['name' => 'CURRENT_MONTH', 'description' => 'Mes actual (nombre)',     'example_value' => 'Enero',      'category' => 'date', 'is_system' => true],

            // LINKS
            ['name' => 'LOGIN_URL',            'description' => 'URL para acceder a la cuenta',          'example_value' => 'https://www.example.com/login',                 'category' => 'links', 'is_system' => true],
            ['name' => 'RESET_PASSWORD_URL',   'description' => 'URL para restablecer contraseña',       'example_value' => 'https://www.example.com/reset-password/token', 'category' => 'links', 'is_system' => true],
            ['name' => 'VERIFY_EMAIL_URL',     'description' => 'URL para verificar email',              'example_value' => 'https://www.example.com/verify-email/token',   'category' => 'links', 'is_system' => true],
            ['name' => 'ACCOUNT_DASHBOARD_URL', 'description' => 'URL del panel de cuenta del usuario',  'example_value' => 'https://www.example.com/dashboard',             'category' => 'links', 'is_system' => true],
            ['name' => 'UNSUBSCRIBE_URL',      'description' => 'URL para desuscribirse de correos',     'example_value' => 'https://www.example.com/unsubscribe/token',     'category' => 'links', 'is_system' => true],
        ];

        $count = 0;
        foreach ($variables as $variable) {
            $mailerVariable = MailerVariable::updateOrCreate(
                ['key' => Str::lower(str_replace(' ', '_', $variable['name']))],
                [
                    'name' => $variable['name'],
                    'description' => $variable['description'],
                    'example_value' => $variable['example_value'],
                    'category' => $variable['category'],
                    'module' => 'core',
                    'is_system' => $variable['is_system'],
                    'is_enabled' => true,
                ]
            );

            if (! $mailerVariable->uid) {
                $mailerVariable->update(['uid' => (string) Str::uuid()]);
            }

            foreach ($locales as $locale) {
                MailerVariableLang::updateOrCreate(
                    ['mailer_variable_id' => $mailerVariable->id, 'lang_id' => $locale->id],
                    [
                        'name' => $variable['name'],
                        'description' => $variable['description'],
                        'value' => $variable['example_value'],
                    ]
                );
            }

            $count++;
        }

        $this->command->info("✓ Variables seeded ({$count} variables, {$locales->count()} locales)");
        $this->command->line('  Categories: user, site, company, date, links');
    }
}
