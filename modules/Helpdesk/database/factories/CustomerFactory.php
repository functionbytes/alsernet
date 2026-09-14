<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'avatar_url' => null,
            'country' => fake()->countryCode(),
            'state' => fake()->state(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'language' => fake()->randomElement(['es', 'en', 'pt']),
            'timezone' => fake()->timezone(),
            'custom_attributes' => null,
            'email_verified_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'banned_at' => null,
            'ban_reason' => null,
            'last_seen_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'total_conversations' => fake()->numberBetween(0, 20),
            'total_page_visits' => fake()->numberBetween(0, 100),
            'internal_notes' => null,
            'whatsapp_phone' => null,
            'facebook_psid' => null,
            'instagram_id' => null,
            'portal_token' => null,
            'portal_token_expires_at' => null,
            'portal_password' => null,
        ];
    }

    public function banned(): static
    {
        return $this->state([
            'banned_at' => now()->subDays(fake()->numberBetween(1, 90)),
            'ban_reason' => fake()->sentence(),
        ]);
    }

    public function verified(): static
    {
        return $this->state([
            'email_verified_at' => now()->subDays(fake()->numberBetween(1, 365)),
        ]);
    }

    public function unverified(): static
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }
}
