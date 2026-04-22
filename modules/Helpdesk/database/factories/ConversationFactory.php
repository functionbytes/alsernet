<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\Conversation;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerFactory::new(),
            'subject' => fake()->sentence(6),
            'status_id' => null,
            'assignee_id' => null,
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'is_archived' => false,
            'channel' => fake()->randomElement(['widget', 'email', 'whatsapp', 'facebook', 'instagram']),
            'external_id' => null,
            'external_sender_id' => null,
            'assigned_at' => null,
            'closed_at' => null,
            'first_response_at' => null,
            'last_message_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'tags' => null,
        ];
    }

    public function assigned(int $userId): static
    {
        return $this->state([
            'assignee_id' => $userId,
            'assigned_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function archived(): static
    {
        return $this->state([
            'is_archived' => true,
            'closed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function viaWhatsapp(): static
    {
        return $this->state([
            'channel' => 'whatsapp',
            'external_id' => (string) fake()->numberBetween(1000000000, 9999999999),
        ]);
    }
}
