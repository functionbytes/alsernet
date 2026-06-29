<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Mailrelay\Entities\MailProvider;

class MailProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Provider 1: Mailrelay (Producción)
        MailProvider::create([
            'name' => 'Mailrelay Producción',
            'driver' => 'mailrelay',
            'credentials' => [
                'api_url' => env('MAILRELAY_API_URL', 'https://app.mailrelay.com/api/v1'),
                'api_key' => env('MAILRELAY_API_KEY', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@alsernet.com'),
                'from_name' => env('MAIL_FROM_NAME', 'Alsernet'),
                'reply_to' => env('MAIL_REPLY_TO', 'soporte@alsernet.com'),
                'rate_limit_hour' => 10000,
                'rate_limit_day' => 100000,
                'batch_size' => 1000,
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 10,
            'limits' => [
                'emails_per_hour' => 10000,
                'emails_per_day' => 100000,
            ],
            'metadata' => [
                'description' => 'Provider principal para envíos masivos en producción',
                'use_cases' => ['Newsletters', 'Campaigns', 'Marketing'],
            ],
        ]);

        // Provider 2: Mailtrap (Testing/Staging)
        MailProvider::create([
            'name' => 'Mailtrap Testing',
            'driver' => 'mailtrap',
            'credentials' => [
                'api_token' => env('MAILTRAP_API_TOKEN', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', 'test@alsernet.com'),
                'from_name' => env('MAIL_FROM_NAME', 'Alsernet Testing'),
            ],
            'is_active' => env('APP_ENV') !== 'production', // Solo en testing/staging
            'is_default' => false,
            'priority' => 5,
            'limits' => [
                'emails_per_second' => 100,
                'emails_per_hour' => 10000,
            ],
            'metadata' => [
                'description' => 'Provider para testing y staging',
                'use_cases' => ['Testing', 'QA', 'Preview'],
            ],
        ]);

        // Provider 3: Mailrelay Backup (Failover)
        MailProvider::create([
            'name' => 'Mailrelay Backup',
            'driver' => 'mailrelay',
            'credentials' => [
                'api_url' => env('MAILRELAY_BACKUP_API_URL', 'https://app.mailrelay.com/api/v1'),
                'api_key' => env('MAILRELAY_BACKUP_API_KEY', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@alsernet.com'),
                'from_name' => env('MAIL_FROM_NAME', 'Alsernet'),
                'reply_to' => env('MAIL_REPLY_TO', 'soporte@alsernet.com'),
            ],
            'is_active' => false, // Desactivado por defecto, se activa si el principal falla
            'is_default' => false,
            'priority' => 8,
            'metadata' => [
                'description' => 'Provider de respaldo para failover',
                'use_cases' => ['Failover', 'Backup'],
            ],
        ]);

        $this->command->info('✅ Mail providers creados exitosamente');
    }
}
