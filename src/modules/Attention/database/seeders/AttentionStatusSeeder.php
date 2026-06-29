<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Attention\Enums\AttentionStatus;

class AttentionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Este seeder es solo documentación.
     * Los status se manejan vía enum AttentionStatus:
     * - RECEIVED (Recibido)
     * - IN_PROCESS (En Proceso)
     * - RESOLVED (Resuelto)
     * - CLOSED (Cerrado)
     */
    public function run(): void
    {
        \Log::info('AttentionStatus se maneja vía enum, no requiere seeder de base de datos');

        $this->command->info('Status disponibles:');
        foreach (AttentionStatus::cases() as $status) {
            $this->command->info("  - {$status->value}: {$status->label()}");
        }
    }
}
