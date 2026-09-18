<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Automation\AutomationChain;

class AutomationChainFactory extends Factory
{
    protected $model = AutomationChain::class;

    public function definition(): array
    {
        return [
            'uid' => Str::ulid(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'chain_definition' => [
                'stages' => [
                    ['workflow' => 'extract', 'order' => 1],
                    ['workflow' => 'generate', 'order' => 2],
                ],
            ],
            'fail_strategy' => $this->faker->randomElement(['halt', 'skip', 'compensate']),
            'parallel_stages' => false,
            'max_parallel' => 1,
            'timeout_minutes' => $this->faker->numberBetween(5, 60),
            'is_enabled' => true,
        ];
    }
}
