<?php

namespace Modules\Reviews\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleLocation;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $rating = fake()->randomElement(['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE']);
        $reviewTime = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'location_id' => ReviewGoogleLocation::factory(),
            'google_review_id' => 'accounts/*/locations/*/reviews/'.fake()->uuid(),
            'reviewer_name' => fake()->name(),
            'reviewer_photo_url' => fake()->imageUrl(100, 100, 'people'),
            'star_rating' => $rating,
            'comment' => $this->getCommentByRating($rating),
            'review_time' => $reviewTime,
            'update_time' => fake()->boolean(30) ? fake()->dateTimeBetween($reviewTime, 'now') : null,
            'google_reply_text' => null,
            'google_reply_time' => null,
            'raw_json' => [
                'reviewId' => fake()->uuid(),
                'reviewer' => ['displayName' => fake()->name()],
            ],
            'synced_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
        ];
    }

    public function oneStar(): static
    {
        return $this->state([
            'star_rating' => 'ONE',
            'comment' => fake()->randomElement([
                'Terrible experience. Would not recommend.',
                'Very disappointed with the service.',
                'Poor quality, waste of money.',
            ]),
        ]);
    }

    public function twoStars(): static
    {
        return $this->state([
            'star_rating' => 'TWO',
            'comment' => fake()->randomElement([
                'Below expectations. Needs improvement.',
                'Not satisfied with the service.',
                'Several issues that need to be addressed.',
            ]),
        ]);
    }

    public function threeStars(): static
    {
        return $this->state([
            'star_rating' => 'THREE',
            'comment' => fake()->randomElement([
                'Average experience. Nothing special.',
                'Acceptable but could be better.',
                'Met basic expectations.',
            ]),
        ]);
    }

    public function fourStars(): static
    {
        return $this->state([
            'star_rating' => 'FOUR',
            'comment' => fake()->randomElement([
                'Good experience overall. Recommended.',
                'Very satisfied with the service.',
                'Great quality, minor improvements needed.',
            ]),
        ]);
    }

    public function fiveStars(): static
    {
        return $this->state([
            'star_rating' => 'FIVE',
            'comment' => fake()->randomElement([
                'Excellent service! Highly recommended!',
                'Amazing experience, will definitely come back.',
                'Outstanding quality and customer service.',
            ]),
        ]);
    }

    public function withGoogleReply(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'google_reply_text' => 'Thank you for your feedback! We appreciate your business.',
                'google_reply_time' => fake()->dateTimeBetween($attributes['review_time'], 'now'),
            ];
        });
    }

    public function noComment(): static
    {
        return $this->state([
            'comment' => null,
        ]);
    }

    private function getCommentByRating(string $rating): ?string
    {
        $comments = [
            'ONE' => [
                'Terrible experience. Would not recommend.',
                'Very disappointed with the service.',
                'Poor quality, waste of money.',
            ],
            'TWO' => [
                'Below expectations. Needs improvement.',
                'Not satisfied with the service.',
                'Several issues that need to be addressed.',
            ],
            'THREE' => [
                'Average experience. Nothing special.',
                'Acceptable but could be better.',
                'Met basic expectations.',
            ],
            'FOUR' => [
                'Good experience overall. Recommended.',
                'Very satisfied with the service.',
                'Great quality, minor improvements needed.',
            ],
            'FIVE' => [
                'Excellent service! Highly recommended!',
                'Amazing experience, will definitely come back.',
                'Outstanding quality and customer service.',
            ],
        ];

        return fake()->boolean(80) ? fake()->randomElement($comments[$rating]) : null;
    }
}
