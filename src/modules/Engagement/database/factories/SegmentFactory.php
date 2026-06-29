<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\Segment;

class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['field' => 'score', 'operator' => 'gte', 'value' => 50],
                ],
            ],
            'is_active' => true,
        ];
    }

    public function highValue(): static
    {
        return $this->state([
            'name' => 'Alto valor',
            'conditions' => [
                'operator' => 'AND',
                'rules' => [
                    ['field' => 'score', 'operator' => 'gte', 'value' => 80],
                    ['field' => 'event_count', 'operator' => 'gte', 'value' => 5],
                ],
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
