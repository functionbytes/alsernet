<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialIntent;

class SocialIntentFactory extends Factory
{
    protected $model = SocialIntent::class;

    public function definition(): array
    {
        return [
            'classifiable_type' => SocialComment::class,
            'classifiable_id' => SocialComment::factory(),
            'platform' => $this->faker->randomElement(['facebook', 'instagram']),
            'intent' => $this->faker->randomElement(['query', 'complaint', 'purchase_interest', 'positive', 'neutral']),
            'confidence' => $this->faker->randomFloat(2, 0.5, 1.0),
            'classifier' => 'rules',
            'urgency' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'keywords_matched' => $this->faker->words(3),
            'entities' => [],
            'raw_response' => null,
            'classified_at' => now(),
        ];
    }
}
