<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceWebhook;

/**
 * @extends Factory<SourceWebhook>
 */
class SourceWebhookFactory extends Factory
{
    protected $model = SourceWebhook::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'uid' => (string) Str::ulid(),
            'source_id' => Source::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'endpoint_path' => "/webhooks/supplier/{$slug}",
            // Stored encrypted by the model cast; keep short so the ciphertext fits the 255-char column.
            'secret_key' => Str::random(16),
            'events' => fake()->randomElements(['inventory.updated', 'price.changed', 'product.created', 'product.deleted'], 2),
            'payload_mapping' => ['sku' => 'data.sku', 'stock' => 'data.quantity'],
            'processing_mode' => fake()->randomElement(['sync', 'async', 'batch']),
            'batch_size' => fake()->randomElement([50, 100, 250]),
            'batch_window_seconds' => fake()->randomElement([30, 60, 120]),
            'auth_type' => fake()->randomElement(['signature', 'bearer', 'basic', 'none']),
            // Left null: the model's encrypted:array cast yields a non-JSON string that
            // would violate the json_valid() CHECK constraint MariaDB enforces on this column.
            'auth_config' => null,
            'is_enabled' => true,
            'last_received_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'total_received' => fake()->numberBetween(0, 500),
        ];
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }

    public function async(): static
    {
        return $this->state(['processing_mode' => 'async']);
    }
}
