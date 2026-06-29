<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Engagement\Models\EmailCampaign;

class EmailCampaignSeeder extends Seeder
{
    public function run(): void
    {
        if (EmailCampaign::query()->exists()) {
            return;
        }

        EmailCampaign::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Newsletter mensual', 'subject' => 'Novedades de mayo', 'status' => 'sent'],
                ['name' => 'Promoción verano', 'subject' => '20% de descuento', 'status' => 'scheduled', 'scheduled_at' => now()->addDays(3)],
                ['name' => 'Bienvenida nuevos usuarios', 'subject' => 'Bienvenido a bordo', 'status' => 'draft'],
            )
            ->create();
    }
}
