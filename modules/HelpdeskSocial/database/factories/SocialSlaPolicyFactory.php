<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;

class SocialSlaPolicyFactory extends Factory
{
    protected $model = SocialSlaPolicy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'response_time_minutes' => $this->faker->numberBetween(15, 120),
            'resolution_time_minutes' => $this->faker->numberBetween(60, 480),
            'platform' => $this->faker->randomElement(['facebook', 'instagram', 'whatsapp', 'tiktok', 'x', 'linkedin']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'is_active' => true,
            'conditions' => null,
        ];
    }
}
