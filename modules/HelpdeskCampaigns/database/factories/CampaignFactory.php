<?php

namespace Modules\HelpdeskCampaigns\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskCampaigns\Models\Campaign;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['popup', 'banner', 'slide-in', 'full-screen']),
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'active', 'ended', 'paused']),
            'content' => [],
            'appearance' => [
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'primary_color' => '#b10100',
            ],
            'conditions' => [],
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

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'published_at' => now()->addDay(),
        ]);
    }
}
