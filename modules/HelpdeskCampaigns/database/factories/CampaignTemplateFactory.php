<?php

namespace Modules\HelpdeskCampaigns\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskCampaigns\Models\CampaignTemplate;

class CampaignTemplateFactory extends Factory
{
    protected $model = CampaignTemplate::class;

    public function definition(): array
    {
        return [
            'campaign_id' => null,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['newsletter', 'promotion', 'announcement', 'survey', 'feedback', 'custom']),
            'type' => $this->faker->randomElement(['popup', 'banner', 'slide-in', 'full-screen']),
            'content' => [],
            'appearance' => [
                'background_color' => '#ffffff',
                'text_color' => '#000000',
                'primary_color' => '#90bb13',
            ],
            'conditions' => [],
            'thumbnail_url' => null,
            'preview_gradient' => null,
            'is_premium' => false,
            'metadata' => [],
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
        ]);
    }
}
