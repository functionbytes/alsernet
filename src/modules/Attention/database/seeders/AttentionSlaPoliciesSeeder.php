<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Attention\Models\AttentionSlaPolicy;

class AttentionSlaPoliciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds SLA policies with different time requirements for peticiones.
     */
    public function run(): void
    {
        $policies = [
            [
                'name' => 'peticiones Estándar',
                'description' => 'Política de SLA estándar para peticiones según normativa colombiana. Respuesta: 2 días, Resolución: 10 días, Cierre: 15 días',
                'response_time' => 2880, // 2 días (48 horas)
                'resolution_time' => 14400, // 10 días
                'closure_time' => 21600, // 15 días
                'business_hours_only' => false,
                'business_hours' => null,
                'timezone' => 'America/Bogota',
                'type_multipliers' => [
                    'PETICION' => 1.0,
                    'QUEJA' => 0.7,
                    'RECLAMO' => 0.7,
                    'SUGERENCIA' => 1.3,
                    'FELICITACION' => 1.5,
                ],
                'enable_escalation' => true,
                'escalation_threshold_percent' => 80,
                'escalation_recipients' => [
                    ['email' => 'supervisor@example.com', 'role' => 'supervisor'],
                ],
                'active' => true,
                'is_default' => true,
            ],
            [
                'name' => 'peticiones Prioritario',
                'description' => 'Política acelerada para casos prioritarios o urgentes. Tiempos reducidos en 50%',
                'response_time' => 1440, // 1 día
                'resolution_time' => 7200, // 5 días
                'closure_time' => 10800, // 7.5 días
                'business_hours_only' => true,
                'business_hours' => [
                    'monday' => ['start' => '08:00', 'end' => '18:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '18:00'],
                    'thursday' => ['start' => '08:00', 'end' => '18:00'],
                    'friday' => ['start' => '08:00', 'end' => '18:00'],
                    'saturday' => null,
                    'sunday' => null,
                ],
                'timezone' => 'America/Bogota',
                'type_multipliers' => [
                    'PETICION' => 0.8,
                    'QUEJA' => 0.5,
                    'RECLAMO' => 0.5,
                    'SUGERENCIA' => 1.0,
                    'FELICITACION' => 1.2,
                ],
                'enable_escalation' => true,
                'escalation_threshold_percent' => 70,
                'escalation_recipients' => [
                    ['email' => 'manager@example.com', 'role' => 'manager'],
                    ['email' => 'supervisor@example.com', 'role' => 'supervisor'],
                ],
                'active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'peticiones Emergencia',
                'description' => 'Política para situaciones críticas que requieren atención inmediata',
                'response_time' => 480, // 8 horas
                'resolution_time' => 2880, // 2 días
                'closure_time' => 4320, // 3 días
                'business_hours_only' => false,
                'business_hours' => null,
                'timezone' => 'America/Bogota',
                'type_multipliers' => [
                    'PETICION' => 0.5,
                    'QUEJA' => 0.3,
                    'RECLAMO' => 0.3,
                    'SUGERENCIA' => 0.8,
                    'FELICITACION' => 1.0,
                ],
                'enable_escalation' => true,
                'escalation_threshold_percent' => 50,
                'escalation_recipients' => [
                    ['email' => 'director@example.com', 'role' => 'director'],
                    ['email' => 'manager@example.com', 'role' => 'manager'],
                ],
                'active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'peticiones Extendido',
                'description' => 'Política con tiempos extendidos para casos complejos que requieren investigación',
                'response_time' => 4320, // 3 días
                'resolution_time' => 28800, // 20 días
                'closure_time' => 43200, // 30 días
                'business_hours_only' => false,
                'business_hours' => null,
                'timezone' => 'America/Bogota',
                'type_multipliers' => [
                    'PETICION' => 1.5,
                    'QUEJA' => 1.0,
                    'RECLAMO' => 1.0,
                    'SUGERENCIA' => 2.0,
                    'FELICITACION' => 2.0,
                ],
                'enable_escalation' => false,
                'escalation_threshold_percent' => null,
                'escalation_recipients' => null,
                'active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($policies as $policyData) {
            AttentionSlaPolicy::updateOrCreate(
                ['name' => $policyData['name']],
                $policyData
            );
        }
    }
}
