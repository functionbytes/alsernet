<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialRule;

class SocialRuleFactory extends Factory
{
    protected $model = SocialRule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'platform' => $this->faker->randomElement(['facebook', 'instagram', null]),
            'conditions' => [
                ['field' => 'intent', 'operator' => 'equals', 'value' => 'complaint'],
            ],
            'actions' => [
                ['type' => 'escalate', 'params' => []],
            ],
            'priority' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'stop_processing' => false,
            'trigger_count' => 0,
            'valid_from' => now()->subDays(30),
            'valid_until' => null,
            'created_by_user_id' => 1,
        ];
    }
}
