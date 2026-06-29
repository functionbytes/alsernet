<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\AutomationFlow;
use Modules\Engagement\Models\AutomationRun;

class AutomationRunFactory extends Factory
{
    protected $model = AutomationRun::class;

    public function definition(): array
    {
        return [
            'flow_id' => AutomationFlow::factory(),
            'session_token' => $this->faker->uuid,
            'status' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'current_node_id' => $this->faker->uuid,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
