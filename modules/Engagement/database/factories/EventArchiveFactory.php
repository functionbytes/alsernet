<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\EventArchive;

class EventArchiveFactory extends Factory
{
    protected $model = EventArchive::class;

    public function definition(): array
    {
        return [
            'session_token' => $this->faker->uuid(),
            'inbox_id' => 1,
            'customer_id' => $this->faker->optional()->randomNumber(4),
            'event_name' => $this->faker->randomElement(['page_view', 'click', 'add_to_cart', 'purchase', 'scroll']),
            'platform' => 'web',
            'properties' => ['url' => $this->faker->url()],
            'occurred_at' => $this->faker->dateTimeBetween('-120 days', '-90 days'),
            'archived_at' => now(),
        ];
    }
}
