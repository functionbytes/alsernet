<?php

namespace Modules\Engagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Engagement\Models\Segment;

class SegmentSeeder extends Seeder
{
    public function run(): void
    {
        if (Segment::query()->exists()) {
            return;
        }

        Segment::factory()
            ->highValue()
            ->create([
                'name' => 'Visitantes hot',
                'description' => 'Score alto y múltiples eventos',
            ]);

        Segment::factory()
            ->create([
                'name' => 'España',
                'description' => 'Visitantes desde España',
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['field' => 'country', 'operator' => 'eq', 'value' => 'ES'],
                    ],
                ],
            ]);

        Segment::factory()
            ->inactive()
            ->create([
                'name' => 'Baja participación',
                'description' => 'Score bajo y pocas visitas',
                'conditions' => [
                    'operator' => 'AND',
                    'rules' => [
                        ['field' => 'score', 'operator' => 'lt', 'value' => 20],
                        ['field' => 'event_count', 'operator' => 'lt', 'value' => 3],
                    ],
                ],
            ]);
    }
}
