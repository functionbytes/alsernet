<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\BusinessHour;

class BusinessHoursSeeder extends Seeder
{
    public function run(): void
    {
        BusinessHour::initializeDefaults();

        $this->command->info('✅ Horarios de atención inicializados (Lun–Vie 09:00–18:00, Sáb–Dom cerrado)');
    }
}
