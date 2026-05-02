<?php

namespace Modules\Social\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Social\Models\SocialListeningKeyword;

class SocialListeningKeywordFactory extends Factory
{
    protected $model = SocialListeningKeyword::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['keyword', 'hashtag', 'mention', 'competitor']);

        return [
            'account_id' => 1,
            'name' => $this->generateName($type),
            'type' => $type,
            'networks' => fake()->randomElements(['facebook', 'instagram', 'twitter', 'linkedin'], fake()->numberBetween(1, 3)),
            'is_active' => fake()->boolean(80),
            'sentiment_filter' => fake()->randomElement(['all', 'positive', 'negative', 'neutral']),
            'notify_on_match' => fake()->boolean(50),
            'color' => fake()->hexColor(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => 1,
            'mentions_count' => fake()->numberBetween(0, 100),
            'last_scanned_at' => fake()->optional()->dateTimeBetween('-1 day', 'now'),
        ];
    }

    protected function generateName(string $type): string
    {
        return match ($type) {
            'hashtag' => fake()->word(),
            'mention' => fake()->userName(),
            'competitor' => fake()->company(),
            default => fake()->words(2, true),
        };
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function hashtag(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'hashtag',
            'name' => fake()->word(),
        ]);
    }

    public function mention(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'mention',
            'name' => fake()->userName(),
        ]);
    }
}
