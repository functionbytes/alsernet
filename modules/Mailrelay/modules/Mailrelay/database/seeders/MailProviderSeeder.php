<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Mailrelay\Entities\MailProvider;

class MailProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * IMPORTANTE: Este seeder crea providers de ejemplo con credenciales FICTICIAS.
     * En producción, debes configurar las credenciales reales desde el panel de administración.
     */
    public function run(): void
    {
        // Limpiar providers existentes (solo en desarrollo)
        if (app()->environment('local')) {
            MailProvider::query()->delete();
            $this->command->info('Existing providers cleared (local environment only)');
        }

        // Provider 1: Mailrelay (por defecto)
        MailProvider::create([
            'name' => 'Mailrelay Principal',
            'driver' => 'mailrelay',
            'credentials' => [
                'api_url' => 'https://api.mailrelay.com/v1',
                'api_key' => 'YOUR_MAILRELAY_API_KEY_HERE',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Example Team',
                'reply_to' => 'support@example.com',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 100,
            'limits' => [
                'max_per_day' => 50000,
                'max_per_hour' => 5000,
            ],
            'metadata' => [
                'description' => 'Provider principal para campañas de marketing',
                'environment' => 'production',
            ],
            'connection_ok' => false, // Cambiar a true después de configurar credenciales reales
            'last_tested_at' => null,
        ]);

        // Provider 2: Mailtrap (para testing)
        MailProvider::create([
            'name' => 'Mailtrap Development',
            'driver' => 'mailtrap',
            'credentials' => [
                'api_token' => 'YOUR_MAILTRAP_API_TOKEN_HERE',
                'from_email' => 'dev@example.com',
                'from_name' => 'Development Team',
                'reply_to' => 'dev@example.com',
            ],
            'is_active' => false,
            'is_default' => false,
            'priority' => 50,
            'limits' => [
                'max_per_day' => 1000,
                'max_per_hour' => 100,
            ],
            'metadata' => [
                'description' => 'Provider para testing en desarrollo',
                'environment' => 'development',
            ],
            'connection_ok' => false,
            'last_tested_at' => null,
        ]);

        // Provider 3: SendGrid (backup/failover)
        MailProvider::create([
            'name' => 'SendGrid Backup',
            'driver' => 'sendgrid',
            'credentials' => [
                'api_key' => 'YOUR_SENDGRID_API_KEY_HERE',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Example Team',
                'reply_to' => 'support@example.com',
            ],
            'is_active' => false,
            'is_default' => false,
            'priority' => 90,
            'limits' => [
                'max_per_day' => 100000,
                'max_per_hour' => 10000,
            ],
            'metadata' => [
                'description' => 'Provider de respaldo con alto volumen',
                'environment' => 'production',
            ],
            'connection_ok' => false,
            'last_tested_at' => null,
        ]);

        // Provider 4: AWS SES (alto volumen)
        MailProvider::create([
            'name' => 'AWS SES High Volume',
            'driver' => 'aws_ses',
            'credentials' => [
                'access_key' => 'YOUR_AWS_ACCESS_KEY_HERE',
                'secret_key' => 'YOUR_AWS_SECRET_KEY_HERE',
                'region' => 'us-east-1',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Example Team',
                'reply_to' => 'support@example.com',
                'configuration_set' => 'production-emails',
            ],
            'is_active' => false,
            'is_default' => false,
            'priority' => 80,
            'limits' => [
                'max_per_day' => 200000,
                'max_per_hour' => 20000,
            ],
            'metadata' => [
                'description' => 'AWS SES para campañas masivas',
                'environment' => 'production',
                'note' => 'Requiere verificación de dominio en AWS',
            ],
            'connection_ok' => false,
            'last_tested_at' => null,
        ]);

        // Provider 5: Postmark (transaccional)
        MailProvider::create([
            'name' => 'Postmark Transactional',
            'driver' => 'postmark',
            'credentials' => [
                'server_token' => 'YOUR_POSTMARK_SERVER_TOKEN_HERE',
                'from_email' => 'notifications@example.com',
                'from_name' => 'Example Notifications',
                'reply_to' => 'support@example.com',
                'message_stream' => 'outbound',
            ],
            'is_active' => false,
            'is_default' => false,
            'priority' => 70,
            'limits' => [
                'max_per_day' => 10000,
                'max_per_hour' => 1000,
            ],
            'metadata' => [
                'description' => 'Postmark para emails transaccionales',
                'environment' => 'production',
            ],
            'connection_ok' => false,
            'last_tested_at' => null,
        ]);

        $this->command->info('✓ Mail Providers seeded successfully!');
        $this->command->info('  Created 5 providers: Mailrelay, Mailtrap, SendGrid, AWS SES, Postmark');
        $this->command->warn('  ⚠ Remember to update credentials in the settings panel before use!');
        $this->command->warn('  ⚠ Test each provider connection before activating them.');
    }
}
