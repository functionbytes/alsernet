<?php

namespace Database\Seeders;

use App\Models\AlertThreshold;
use Illuminate\Database\Seeder;

class AlertThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            [
                'name' => 'Tickets sin respuesta > 24h',
                'type' => 'tickets_overdue',
                'conditions' => ['hours' => 24, 'min_count' => 1],
                'channels' => ['mail', 'database'],
                'cooldown_minutes' => 120,
                'is_active' => true,
            ],
            [
                'name' => 'Avalancha de reseñas negativas',
                'type' => 'reviews_negative',
                'conditions' => ['hours' => 2, 'min_count' => 3, 'max_rating' => 2],
                'channels' => ['mail', 'database'],
                'cooldown_minutes' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Formularios sin leer acumulados',
                'type' => 'forms_unread',
                'conditions' => ['min_count' => 20],
                'channels' => ['database'],
                'cooldown_minutes' => 240,
                'is_active' => true,
            ],
            [
                'name' => 'PQRSF pendientes en exceso',
                'type' => 'attention_pending',
                'conditions' => ['min_count' => 10],
                'channels' => ['mail', 'database'],
                'cooldown_minutes' => 180,
                'is_active' => true,
            ],
        ];

        foreach ($thresholds as $data) {
            AlertThreshold::firstOrCreate(
                ['name' => $data['name'], 'type' => $data['type']],
                $data
            );
        }
    }
}
