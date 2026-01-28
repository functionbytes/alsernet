<?php

namespace Modules\Mailrelay\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Enums\SubscriberStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscriber>
 */
class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'status' => SubscriberStatus::ACTIVE,
            'mailrelay_id' => null,
            'metadata' => [],
            'custom_fields' => [],
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the subscriber is unsubscribed.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Indicate that the subscriber is bounced.
     */
    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::BOUNCED,
        ]);
    }

    /**
     * Indicate that the subscriber is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::PENDING,
            'subscribed_at' => null,
        ]);
    }

    /**
     * Indicate that the subscriber is banned.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::BANNED,
        ]);
    }

    /**
     * Indicate that the subscriber is synced with Mailrelay.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'mailrelay_id' => fake()->randomNumber(8),
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Indicate that the subscriber has custom metadata.
     */
    public function withMetadata(array $metadata = []): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata ?: [
                'source' => fake()->randomElement(['website', 'api', 'import']),
                'ip' => fake()->ipv4(),
            ],
        ]);
    }
}
