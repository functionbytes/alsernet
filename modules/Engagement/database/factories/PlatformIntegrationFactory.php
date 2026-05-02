<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Engagement\Models\PlatformIntegration;

class PlatformIntegrationFactory extends Factory
{
    protected $model = PlatformIntegration::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'platform' => $this->faker->randomElement(['prestashop', 'shopify', 'woocommerce', 'custom']),
            'store_url' => $this->faker->url(),
            'webhook_secret' => Str::random(64),
            'config' => [],
            'is_active' => true,
            'last_event_at' => null,
        ];
    }

    public function prestashop(): static
    {
        return $this->state(['platform' => 'prestashop']);
    }

    public function shopify(): static
    {
        return $this->state(['platform' => 'shopify']);
    }

    public function woocommerce(): static
    {
        return $this->state(['platform' => 'woocommerce']);
    }

    public function custom(): static
    {
        return $this->state(['platform' => 'custom']);
    }
}
