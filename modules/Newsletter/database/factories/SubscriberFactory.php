<?php

namespace Modules\Newsletter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Newsletter\Models\Subscriber;

class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->optional()->name(),
            'status' => fake()->randomElement([0, 1]),
            'ip_address' => fake()->ipv4(),
            'subscribed_at' => now(),
        ];
    }

    public function subscribed(): static
    {
        return $this->state([
            'status' => 1,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state([
            'status' => 0,
            'unsubscribed_at' => now(),
        ]);
    }
}
