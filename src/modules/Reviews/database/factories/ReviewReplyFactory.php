<?php

namespace Modules\Reviews\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;

class ReviewReplyFactory extends Factory
{
    protected $model = ReviewReply::class;

    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'reply_text' => fake()->paragraph(),
            'status' => 'draft',
            'error_message' => null,
            'error_count' => 0,
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state([
            'status' => 'draft',
            'error_message' => null,
            'error_count' => 0,
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_by' => User::factory(),
                'approved_at' => now()->subHours(fake()->numberBetween(1, 24)),
                'published_at' => null,
            ];
        });
    }

    public function published(): static
    {
        return $this->state(function (array $attributes) {
            $approvedAt = now()->subDays(fake()->numberBetween(1, 7));

            return [
                'status' => 'published',
                'approved_by' => User::factory(),
                'approved_at' => $approvedAt,
                'published_at' => $approvedAt->copy()->addMinutes(fake()->numberBetween(5, 60)),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => fake()->randomElement([
                    'Google API error: Insufficient permissions',
                    'Authentication failed: Invalid token',
                    'Review no longer exists',
                    'Network timeout: Connection refused',
                ]),
                'error_count' => fake()->numberBetween(1, 5),
                'approved_by' => User::factory(),
                'approved_at' => now()->subHours(fake()->numberBetween(1, 48)),
            ];
        });
    }

    public function positive(): static
    {
        return $this->state([
            'reply_text' => fake()->randomElement([
                'Thank you so much for your wonderful feedback! We\'re thrilled to hear you had a great experience.',
                'We appreciate your kind words and are glad we could exceed your expectations!',
                'Thank you for taking the time to share your positive experience. We look forward to serving you again!',
            ]),
        ]);
    }

    public function negative(): static
    {
        return $this->state([
            'reply_text' => fake()->randomElement([
                'We sincerely apologize for your experience. Please contact us directly so we can make this right.',
                'Thank you for bringing this to our attention. We take all feedback seriously and are working to improve.',
                'We\'re sorry to hear about your disappointment. We\'d love the opportunity to discuss this further.',
            ]),
        ]);
    }
}
