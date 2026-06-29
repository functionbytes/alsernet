<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialMetrics;

class SocialMetricFactory extends Factory
{
    protected $model = SocialMetrics::class;

    public function definition(): array
    {
        return [
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'social_account_id' => null,
            'platform' => $this->faker->randomElement(['facebook', 'instagram']),
            'comments_received' => $this->faker->numberBetween(0, 200),
            'messages_received' => $this->faker->numberBetween(0, 100),
            'replies_sent' => $this->faker->numberBetween(0, 150),
            'auto_replies_sent' => $this->faker->numberBetween(0, 80),
            'manual_replies_sent' => $this->faker->numberBetween(0, 70),
            'escalated_count' => $this->faker->numberBetween(0, 20),
            'spam_detected' => $this->faker->numberBetween(0, 10),
            'avg_response_time_seconds' => $this->faker->numberBetween(30, 7200),
            'first_response_time_seconds' => $this->faker->numberBetween(30, 3600),
            'automation_rate' => $this->faker->randomFloat(2, 0, 1),
            'intents_breakdown' => ['question' => 10, 'complaint' => 5],
            'sentiment_breakdown' => ['positive' => 8, 'neutral' => 4, 'negative' => 3],
            'hourly_distribution' => array_fill(0, 24, $this->faker->numberBetween(0, 20)),
        ];
    }
}
