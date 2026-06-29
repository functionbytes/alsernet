<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\NewsletterSubscriber;

class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'source' => $this->faker->randomElement(['shop_footer', 'checkout', 'admin_manual']),
            'ip_address' => $this->faker->ipv4(),
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
            'unsubscribe_token' => NewsletterSubscriber::generateUnsubscribeToken(),
        ];
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSubscriber::STATUS_BOUNCED,
        ]);
    }
}
