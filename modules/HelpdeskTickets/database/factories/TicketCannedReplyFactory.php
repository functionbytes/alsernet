<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketCannedReply;

class TicketCannedReplyFactory extends Factory
{
    protected $model = TicketCannedReply::class;

    public function definition(): array
    {
        $content = fake()->paragraph();

        return [
            'user_id' => null,
            'title' => fake()->sentence(4),
            'content' => $content,
            'html_body' => '<p>'.$content.'</p>',
            'category' => fake()->randomElement(['general', 'support', 'billing', 'sales']),
            'tags' => [],
            'short_code' => fake()->unique()->lexify('???_???'),
            'is_global' => true,
            'is_active' => true,
            'usage_count' => 0,
        ];
    }

    public function personal(int $userId): static
    {
        return $this->state(['user_id' => $userId, 'is_global' => false]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function popular(): static
    {
        return $this->state(['usage_count' => fake()->numberBetween(50, 500)]);
    }
}
