<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\AutomationFlow;

class AutomationFlowFactory extends Factory
{
    protected $model = AutomationFlow::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'nodes' => [
                ['type' => 'trigger', 'config' => ['event' => 'page_view']],
                ['type' => 'delay', 'config' => ['minutes' => 5]],
                ['type' => 'message', 'config' => ['text' => '¿Necesitas ayuda?']],
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
