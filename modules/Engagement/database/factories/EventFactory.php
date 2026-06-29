<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Engagement\Models\Event;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $eventName = $this->faker->randomElement([
            'page_view', 'product_view', 'add_to_cart', 'checkout_start', 'session_start',
        ]);

        return [
            'session_token' => Str::random(64),
            'customer_id' => null,
            'inbox_id' => 1,
            'event_name' => $eventName,
            'properties' => $this->propertiesFor($eventName),
            'page_url' => $this->faker->url(),
            'page_title' => $this->faker->sentence(4),
            'referrer' => $this->faker->optional()->url(),
            'user_agent' => $this->faker->userAgent(),
            'ip_address' => $this->faker->ipv4(),
            'occurred_at' => now(),
            'received_at' => now(),
        ];
    }

    public function pageView(): static
    {
        return $this->state(['event_name' => 'page_view']);
    }

    public function productView(): static
    {
        return $this->state([
            'event_name' => 'product_view',
            'properties' => $this->propertiesFor('product_view'),
        ]);
    }

    public function addToCart(): static
    {
        return $this->state([
            'event_name' => 'add_to_cart',
            'properties' => $this->propertiesFor('add_to_cart'),
        ]);
    }

    public function purchase(): static
    {
        return $this->state([
            'event_name' => 'purchase',
            'properties' => $this->propertiesFor('purchase'),
        ]);
    }

    protected function propertiesFor(string $eventName): array
    {
        return match ($eventName) {
            'product_view' => [
                'id' => 'SKU'.$this->faker->unique()->numberBetween(1000, 9999),
                'name' => $this->faker->words(3, true),
                'price' => $this->faker->randomFloat(2, 10, 500),
                'currency' => 'EUR',
                'category' => $this->faker->word(),
            ],
            'add_to_cart' => [
                'id' => 'SKU'.$this->faker->numberBetween(1000, 9999),
                'price' => $this->faker->randomFloat(2, 10, 500),
                'currency' => 'EUR',
                'quantity' => $this->faker->numberBetween(1, 3),
            ],
            'checkout_start' => [
                'cartValue' => $this->faker->randomFloat(2, 50, 1000),
                'itemsCount' => $this->faker->numberBetween(1, 5),
                'currency' => 'EUR',
            ],
            'purchase' => [
                'orderId' => 'ORD-'.$this->faker->unique()->numberBetween(10000, 99999),
                'total' => $this->faker->randomFloat(2, 50, 1000),
                'currency' => 'EUR',
                'items' => [
                    [
                        'id' => 'SKU'.$this->faker->numberBetween(1000, 9999),
                        'qty' => 1,
                        'price' => $this->faker->randomFloat(2, 10, 500),
                    ],
                ],
            ],
            'session_start' => [
                'language' => 'es-ES',
                'timezone' => 'Europe/Madrid',
            ],
            default => [],
        };
    }
}
