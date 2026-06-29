<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\WebhookLog;

class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'platform_integration_id' => 1,
            'event_type' => $this->faker->randomElement(['order_created', 'product_updated', 'customer_created']),
            'payload' => ['id' => $this->faker->randomNumber()],
            'status' => $this->faker->randomElement(['pending', 'processed', 'failed']),
            'attempts' => $this->faker->numberBetween(0, 3),
            'error_message' => null,
            'processed_at' => $this->faker->optional()->dateTime(),
        ];
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
            'attempts' => 3,
        ]);
    }
}
