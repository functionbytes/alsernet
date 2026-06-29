<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;

class MailrelayDatabaseSeeder extends Seeder
{
    /**
     * Run the module database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeders de Mailrelay...');

        // 1. Crear mail providers primero (requerido)
        $this->call(MailProviderSeeder::class);

        // 2. Crear campaigns de ejemplo
        $this->call(CampaignSeeder::class);

        $this->command->info('✅ Seeders de Mailrelay completados exitosamente');
    }
}
