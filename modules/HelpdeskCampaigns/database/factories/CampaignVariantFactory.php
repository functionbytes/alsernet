<?php

namespace Modules\HelpdeskCampaigns\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskCampaigns\Models\Campaign;
use Modules\HelpdeskCampaigns\Models\CampaignVariant;

/**
 * @extends Factory<CampaignVariant>
 */
class CampaignVariantFactory extends Factory
{
    protected $model = CampaignVariant::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'label' => $this->faker->randomElement(['A', 'B', 'Control', 'Test']),
            'weight' => $this->faker->numberBetween(10, 90),
            'content' => [
                'headline' => $this->faker->sentence(6),
                'body' => $this->faker->paragraph(2),
                'cta_label' => $this->faker->words(2, true),
                'cta_url' => $this->faker->url(),
            ],
            'appearance' => [
                'bg' => $this->faker->hexColor(),
                'color' => '#222',
                'accent' => '#90bb13',
            ],
            'impressions_count' => 0,
            'clicks_count' => 0,
        ];
    }

    public function withWeight(int $weight): static
    {
        return $this->state(fn () => ['weight' => $weight]);
    }
}
