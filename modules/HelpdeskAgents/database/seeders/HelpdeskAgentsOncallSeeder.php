<?php

namespace Modules\HelpdeskAgents\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskAgents\Models\OncallRotation;

/**
 * Rotacion de guardia de ejemplo para la pantalla Ajustes > Horarios
 * (pestana Guardias), que estaba vacia. Idempotente por nombre.
 */
class HelpdeskAgentsOncallSeeder extends Seeder
{
    public function run(): void
    {
        $agentIds = [1, 25, 27, 32];
        $startedAt = now()->subDays(2)->startOfDay();
        $durationHours = 24;

        OncallRotation::updateOrCreate(
            ['name' => 'Guardia nocturna - Soporte critico'],
            [
                'name' => 'Guardia nocturna - Soporte critico',
                'user_ids' => $agentIds,
                'shift_duration_hours' => $durationHours,
                'started_at' => $startedAt,
                'current_user_id' => $agentIds[0],
                'next_handoff_at' => $startedAt->copy()->addHours($durationHours),
                'is_active' => true,
            ]
        );

        $this->command?->info('Rotacion de guardia de ejemplo creada');
    }
}
