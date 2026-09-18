<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Models\Status;

class StatusesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'New', 'slug' => 'new', 'type' => 'open', 'color' => '#6C757D', 'order' => 0, 'is_default' => true],
            ['name' => 'Open', 'slug' => 'open', 'type' => 'open', 'color' => '#0D6EFD', 'order' => 1, 'is_default' => false],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'type' => 'pending', 'color' => '#0DCAF0', 'order' => 2, 'is_default' => false],
            ['name' => 'On Hold', 'slug' => 'on-hold', 'type' => 'pending', 'color' => '#FFC107', 'order' => 3, 'is_default' => false],
            ['name' => 'Resolved', 'slug' => 'resolved', 'type' => 'resolved', 'color' => '#198754', 'order' => 4, 'is_default' => false],
            ['name' => 'Closed', 'slug' => 'closed', 'type' => 'closed', 'color' => '#6C757D', 'order' => 5, 'is_default' => false],
        ];

        foreach ($statuses as $data) {
            Status::create([
                'uid' => Str::uuid(),
                'name' => $data['name'],
                'slug' => $data['slug'],
                'type' => $data['type'],
                'color' => $data['color'],
                'order' => $data['order'],
                'is_default' => $data['is_default'],
                'is_active' => true,
            ]);
        }
    }
}
