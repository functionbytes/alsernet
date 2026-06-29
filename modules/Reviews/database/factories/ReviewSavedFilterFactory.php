<?php

namespace Modules\Reviews\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\ReviewSavedFilter;

class ReviewSavedFilterFactory extends Factory
{
    protected $model = ReviewSavedFilter::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'filters_json' => $this->generateFilters(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state([
            'name' => 'Default Filter',
            'is_default' => true,
        ]);
    }

    public function highRated(): static
    {
        return $this->state([
            'name' => 'High Rated Reviews',
            'filters_json' => [
                'star_rating' => ['FOUR', 'FIVE'],
                'has_comment' => true,
                'date_from' => now()->subDays(30)->toDateString(),
            ],
        ]);
    }

    public function lowRated(): static
    {
        return $this->state([
            'name' => 'Low Rated Reviews',
            'filters_json' => [
                'star_rating' => ['ONE', 'TWO'],
                'has_comment' => true,
                'date_from' => now()->subDays(7)->toDateString(),
            ],
        ]);
    }

    public function needsReply(): static
    {
        return $this->state([
            'name' => 'Needs Reply',
            'filters_json' => [
                'has_google_reply' => false,
                'has_our_reply' => false,
                'date_from' => now()->subDays(7)->toDateString(),
            ],
        ]);
    }

    public function featured(): static
    {
        return $this->state([
            'name' => 'Featured Reviews',
            'filters_json' => [
                'is_featured' => true,
                'is_visible' => true,
                'star_rating' => ['FOUR', 'FIVE'],
            ],
        ]);
    }

    public function recentWithComment(): static
    {
        return $this->state([
            'name' => 'Recent with Comments',
            'filters_json' => [
                'has_comment' => true,
                'date_from' => now()->subDays(14)->toDateString(),
                'sort_by' => 'review_time',
                'sort_order' => 'desc',
            ],
        ]);
    }

    protected function generateFilters(): array
    {
        $filters = [];

        // Random star rating filter
        if (fake()->boolean(60)) {
            $ratings = ['ONE', 'TWO', 'THREE', 'FOUR', 'FIVE'];
            $filters['star_rating'] = fake()->randomElements($ratings, fake()->numberBetween(1, 3));
        }

        // Random comment filter
        if (fake()->boolean(50)) {
            $filters['has_comment'] = fake()->boolean(80);
        }

        // Random reply filter
        if (fake()->boolean(40)) {
            $filters['has_google_reply'] = fake()->boolean();
        }

        // Random visibility filter
        if (fake()->boolean(30)) {
            $filters['is_visible'] = fake()->boolean(90);
        }

        // Random featured filter
        if (fake()->boolean(20)) {
            $filters['is_featured'] = fake()->boolean(30);
        }

        // Random date range
        if (fake()->boolean(70)) {
            $filters['date_from'] = now()->subDays(fake()->numberBetween(7, 90))->toDateString();
        }

        // Random sort
        if (fake()->boolean(50)) {
            $filters['sort_by'] = fake()->randomElement(['review_time', 'star_rating', 'created_at']);
            $filters['sort_order'] = fake()->randomElement(['asc', 'desc']);
        }

        return $filters;
    }
}
