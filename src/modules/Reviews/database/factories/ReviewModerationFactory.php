<?php

namespace Modules\Reviews\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewModeration;

class ReviewModerationFactory extends Factory
{
    protected $model = ReviewModeration::class;

    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'is_visible' => true,
            'is_featured' => false,
            'tags' => fake()->boolean(50) ? fake()->randomElements(
                ['helpful', 'detailed', 'verified', 'local', 'regular'],
                fake()->numberBetween(1, 3)
            ) : null,
            'internal_notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'moderated_by' => User::factory(),
            'moderated_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    public function hidden(): static
    {
        return $this->state([
            'is_visible' => false,
            'internal_notes' => 'Hidden due to: '.fake()->randomElement([
                'Spam content detected',
                'Inappropriate language',
                'Off-topic review',
                'Suspected fake review',
            ]),
        ]);
    }

    public function featured(): static
    {
        return $this->state([
            'is_featured' => true,
            'is_visible' => true,
            'tags' => ['featured', 'verified', 'detailed'],
        ]);
    }

    public function unmoderated(): static
    {
        return $this->state([
            'moderated_by' => null,
            'moderated_at' => null,
            'tags' => null,
            'internal_notes' => null,
        ]);
    }

    public function withTags(array $tags): static
    {
        return $this->state([
            'tags' => $tags,
        ]);
    }
}
