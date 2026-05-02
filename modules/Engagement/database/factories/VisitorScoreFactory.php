<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Engagement\Models\VisitorScore;

class VisitorScoreFactory extends Factory
{
    protected $model = VisitorScore::class;

    public function definition(): array
    {
        $score = $this->faker->numberBetween(0, 100);

        return [
            'session_token' => Str::random(64),
            'customer_id' => null,
            'inbox_id' => 1,
            'score' => $score,
            'segment' => VisitorScore::segmentFromScore($score),
            'last_event_at' => now(),
            'last_recalc_at' => now(),
        ];
    }

    public function cold(): static
    {
        return $this->state(['score' => $this->faker->numberBetween(0, 24), 'segment' => 'cold']);
    }

    public function warm(): static
    {
        return $this->state(['score' => $this->faker->numberBetween(25, 59), 'segment' => 'warm']);
    }

    public function hot(): static
    {
        return $this->state(['score' => $this->faker->numberBetween(60, 100), 'segment' => 'hot']);
    }
}
