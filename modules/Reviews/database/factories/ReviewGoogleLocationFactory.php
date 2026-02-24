<?php

namespace Modules\Reviews\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;

class ReviewGoogleLocationFactory extends Factory
{
    protected $model = ReviewGoogleLocation::class;

    public function definition(): array
    {
        return [
            'connection_id' => ReviewGoogleConnection::factory(),
            'google_location_id' => 'locations/'.fake()->numerify('##############'),
            'google_account_id' => 'accounts/'.fake()->numerify('##############'),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'website_url' => fake()->url(),
            'average_rating' => fake()->randomFloat(2, 3.5, 5.0),
            'total_reviews' => fake()->numberBetween(10, 500),
            'is_verified' => true,
            'is_active' => true,
            'metadata_json' => [
                'primary_category' => fake()->randomElement(['Restaurant', 'Hotel', 'Store', 'Service']),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ],
            'synced_at' => now()->subMinutes(fake()->numberBetween(5, 60)),
        ];
    }

    public function unverified(): static
    {
        return $this->state([
            'is_verified' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function lowRated(): static
    {
        return $this->state([
            'average_rating' => fake()->randomFloat(2, 2.0, 3.5),
            'total_reviews' => fake()->numberBetween(50, 200),
        ]);
    }

    public function highRated(): static
    {
        return $this->state([
            'average_rating' => fake()->randomFloat(2, 4.5, 5.0),
            'total_reviews' => fake()->numberBetween(100, 1000),
        ]);
    }
}
