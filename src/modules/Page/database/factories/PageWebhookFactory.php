<?php

namespace Modules\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Page\Models\PageWebhook;

class PageWebhookFactory extends Factory
{
    protected $model = PageWebhook::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'url' => 'https://webhook.site/'.$this->faker->uuid(),
            'secret' => $this->faker->optional()->sha256(),
            'events' => ['published', 'updated'],
            'is_active' => true,
            'timeout' => 10,
            'success_count' => 0,
            'fail_count' => 0,
            'last_triggered_at' => null,
            'last_success_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forEvent(string ...$events): static
    {
        return $this->state(['events' => $events]);
    }
}
