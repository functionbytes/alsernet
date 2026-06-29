<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialMention;

class SocialMentionFactory extends Factory
{
    protected $model = SocialMention::class;

    public function definition(): array
    {
        return [
            'platform' => $this->faker->randomElement(['facebook', 'instagram', 'tiktok', 'x', 'linkedin']),
            'external_id' => $this->faker->uuid(),
            'author_name' => $this->faker->name(),
            'author_username' => $this->faker->userName(),
            'body' => $this->faker->sentence(15),
            'url' => $this->faker->url(),
            'matched_keywords' => [$this->faker->word(), $this->faker->word()],
            'sentiment' => $this->faker->randomElement(['positive', 'neutral', 'negative']),
            'sentiment_score' => $this->faker->randomFloat(3, -1, 1),
            'engagement_count' => $this->faker->numberBetween(0, 1000),
            'status' => 'new',
            'discovered_at' => now(),
            'posted_at' => now()->subHours(2),
        ];
    }
}
