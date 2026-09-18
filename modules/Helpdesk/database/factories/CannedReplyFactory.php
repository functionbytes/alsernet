<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\CannedReply;

class CannedReplyFactory extends Factory
{
    protected $model = CannedReply::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(2),
            'html_body' => null,
            'category' => fake()->randomElement(['general', 'billing', 'technical', 'welcome', 'closing']),
            'tags' => null,
            'shortcut' => fake()->optional(0.4)->lexify('???'),
            'is_global' => fake()->boolean(60),
            'usage_count' => fake()->numberBetween(0, 50),
        ];
    }

    public function global(): static
    {
        return $this->state(['is_global' => true]);
    }

    public function private(int $userId): static
    {
        return $this->state([
            'user_id' => $userId,
            'is_global' => false,
        ]);
    }

    public function popular(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(100, 500),
        ]);
    }
}
