<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HelpdeskPrioritiesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $priorities = [
            [
                'slug' => 'baja',
                'name' => 'Baja',
                'level' => 1,
                'color' => '#6C757D',
                'response_time_hours' => 72,
                'resolution_time_hours' => 168,
                'is_active' => 1,
            ],
            [
                'slug' => 'normal',
                'name' => 'Normal',
                'level' => 2,
                'color' => '#0D6EFD',
                'response_time_hours' => 24,
                'resolution_time_hours' => 72,
                'is_active' => 1,
            ],
            [
                'slug' => 'alta',
                'name' => 'Alta',
                'level' => 3,
                'color' => '#FFC107',
                'response_time_hours' => 8,
                'resolution_time_hours' => 24,
                'is_active' => 1,
            ],
            [
                'slug' => 'urgente',
                'name' => 'Urgente',
                'level' => 4,
                'color' => '#FD7E14',
                'response_time_hours' => 2,
                'resolution_time_hours' => 8,
                'is_active' => 1,
            ],
            [
                'slug' => 'critico',
                'name' => 'Crítico',
                'level' => 5,
                'color' => '#DC3545',
                'response_time_hours' => 1,
                'resolution_time_hours' => 4,
                'is_active' => 1,
            ],
        ];

        foreach ($priorities as $priority) {
            $existing = DB::connection('helpdesk')
                ->table('helpdesk_priorities')
                ->where('level', $priority['level'])
                ->first();

            if ($existing) {
                DB::connection('helpdesk')
                    ->table('helpdesk_priorities')
                    ->where('level', $priority['level'])
                    ->update(array_merge($priority, ['updated_at' => $now]));
            } else {
                DB::connection('helpdesk')
                    ->table('helpdesk_priorities')
                    ->insert(array_merge($priority, [
                        'uid' => (string) Str::ulid(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
            }
        }

        $this->command->info('✅ Prioridades de helpdesk creadas ('.count($priorities).')');
    }
}
