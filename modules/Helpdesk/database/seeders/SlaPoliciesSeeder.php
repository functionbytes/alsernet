<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlaPoliciesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Resolve priority IDs from previously seeded priorities
        $priorities = DB::connection('helpdesk')
            ->table('helpdesk_priorities')
            ->pluck('id', 'slug');

        $policies = [
            [
                'name' => 'SLA Prioridad Baja',
                'description' => 'Política estándar para tickets de baja prioridad. Tiempo de respuesta extendido.',
                'priority_slug' => 'baja',
                'first_response_time_hours' => 72,
                'resolution_time_hours' => 168,
                'business_hours_only' => 1,
                'warning_threshold_percent' => 80,
                'is_active' => 1,
            ],
            [
                'name' => 'SLA Prioridad Normal',
                'description' => 'Política estándar para tickets regulares. Respuesta en 24 horas hábiles.',
                'priority_slug' => 'normal',
                'first_response_time_hours' => 24,
                'resolution_time_hours' => 72,
                'business_hours_only' => 1,
                'warning_threshold_percent' => 80,
                'is_active' => 1,
            ],
            [
                'name' => 'SLA Prioridad Alta',
                'description' => 'Política acelerada para tickets de alta prioridad. Respuesta en 8 horas.',
                'priority_slug' => 'alta',
                'first_response_time_hours' => 8,
                'resolution_time_hours' => 24,
                'business_hours_only' => 1,
                'warning_threshold_percent' => 75,
                'is_active' => 1,
            ],
            [
                'name' => 'SLA Urgente',
                'description' => 'Política de atención urgente. Respuesta en 2 horas, resolución en 8 horas.',
                'priority_slug' => 'urgente',
                'first_response_time_hours' => 2,
                'resolution_time_hours' => 8,
                'business_hours_only' => 0,
                'warning_threshold_percent' => 70,
                'is_active' => 1,
            ],
            [
                'name' => 'SLA Crítico 24/7',
                'description' => 'Política de emergencia para incidentes críticos. Respuesta en 1 hora, sin distinción de horario.',
                'priority_slug' => 'critico',
                'first_response_time_hours' => 1,
                'resolution_time_hours' => 4,
                'business_hours_only' => 0,
                'warning_threshold_percent' => 60,
                'is_active' => 1,
            ],
        ];

        foreach ($policies as $policy) {
            $prioritySlug = $policy['priority_slug'];
            unset($policy['priority_slug']);

            $policy['priority_id'] = $priorities[$prioritySlug] ?? null;

            $existing = DB::connection('helpdesk')
                ->table('helpdesk_sla_policies')
                ->whereNull('deleted_at')
                ->where('name', $policy['name'])
                ->first();

            if ($existing) {
                DB::connection('helpdesk')
                    ->table('helpdesk_sla_policies')
                    ->where('id', $existing->id)
                    ->update(array_merge($policy, ['updated_at' => $now]));
            } else {
                DB::connection('helpdesk')
                    ->table('helpdesk_sla_policies')
                    ->insert(array_merge($policy, [
                        'uid' => (string) Str::ulid(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
            }
        }

        $this->command->info('✅ Políticas SLA creadas ('.count($policies).')');
    }
}
