<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Models\Priority;

class PrioritiesSeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'slug' => 'low', 'level' => 1, 'color' => '#6C757D', 'response_time_hours' => 48, 'resolution_time_hours' => 120],
            ['name' => 'Normal', 'slug' => 'normal', 'level' => 2, 'color' => '#17A2B8', 'response_time_hours' => 24, 'resolution_time_hours' => 72],
            ['name' => 'High', 'slug' => 'high', 'level' => 3, 'color' => '#FFC107', 'response_time_hours' => 8, 'resolution_time_hours' => 24],
            ['name' => 'Urgent', 'slug' => 'urgent', 'level' => 4, 'color' => '#FD7E14', 'response_time_hours' => 2, 'resolution_time_hours' => 8],
            ['name' => 'Critical', 'slug' => 'critical', 'level' => 5, 'color' => '#DC3545', 'response_time_hours' => 1, 'resolution_time_hours' => 4],
        ];

        foreach ($priorities as $data) {
            Priority::create([
                'uid' => Str::uuid(),
                'name' => $data['name'],
                'slug' => $data['slug'],
                'level' => $data['level'],
                'color' => $data['color'],
                'response_time_hours' => $data['response_time_hours'],
                'resolution_time_hours' => $data['resolution_time_hours'],
                'is_active' => true,
            ]);
        }
    }
}
