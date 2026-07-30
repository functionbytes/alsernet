<?php

namespace Modules\HelpdeskCampaigns\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;

class CampaignImpressionFactory extends Factory
{
    protected $model = CampaignImpression::class;

    public function definition(): array
    {
        return [
            'impression_id' => (string) Str::uuid(),
            'campaign_id' => null,
            'customer_id' => null,
            'customer_session_id' => null,
            'page_url' => $this->faker->url(),
            'device_type' => $this->faker->randomElement(['mobile', 'tablet', 'desktop']),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'ip_address' => $this->faker->ipv4(),
            'country' => $this->faker->countryCode(),
            'viewed_at' => now(),
            'clicked_at' => null,
            'metadata' => [],
        ];
    }

    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_at' => now()->addSeconds(rand(5, 60)),
        ]);
    }
}
