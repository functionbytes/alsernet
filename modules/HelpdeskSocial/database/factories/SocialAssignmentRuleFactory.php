<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialAssignmentRule;

class SocialAssignmentRuleFactory extends Factory
{
    protected $model = SocialAssignmentRule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'conditions' => [
                ['field' => 'intent', 'operator' => 'equals', 'value' => 'complaint'],
            ],
            'assignee_user_id' => User::factory(),
            'assignment_strategy' => $this->faker->randomElement(['round_robin', 'workload', 'intent']),
            'priority' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
