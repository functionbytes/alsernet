<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Campaigns\Campaign;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['popup', 'banner', 'slide-in', 'full-screen']),
            'status' => 'draft',
            'content' => [
                'title' => fake()->sentence(),
                'message' => fake()->paragraph(),
            ],
            'appearance' => [
                'primary_color' => '#b10100',
                'position' => 'bottom-right',
            ],
            'conditions' => [
                'url_pattern' => '/*',
                'time_on_page' => 5,
            ],
            'metadata' => [],
            'published_at' => null,
            'ends_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
