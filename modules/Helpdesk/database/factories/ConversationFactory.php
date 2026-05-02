<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'customer_id' => CustomerFactory::new(),
            'subject' => fake()->sentence(6),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'channel' => fake()->randomElement(['web', 'email', 'whatsapp', 'facebook', 'instagram']),
            'external_id' => null,
            'external_sender_id' => null,
            'assigned_at' => null,
            'closed_at' => null,
            'first_response_at' => null,
            'last_message_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'tags' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Conversation $conversation) {
            if ($conversation->status_id === null) {
                $status = ConversationStatus::where('is_open', true)->orderBy('order')->first();
                if ($status) {
                    $conversation->status_id = $status->id;
                    $conversation->save();
                }
            }
        });
    }

    public function assigned(int $userId): static
    {
        return $this->state([
            'assignee_id' => $userId,
            'assigned_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ])->afterCreating(function (Conversation $conversation) {
            if ($conversation->assignee_id) {
                $conversation->assignTo($conversation->assignee_id);
            }
        });
    }

    public function archived(): static
    {
        return $this->state([
            'is_archived' => true,
            'closed_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ])->afterCreating(function (Conversation $conversation) {
            $conversation->is_archived = true;
            $conversation->save();
        });
    }

    public function viaWhatsapp(): static
    {
        return $this->state([
            'channel' => 'whatsapp',
            'external_id' => (string) fake()->numberBetween(1000000000, 9999999999),
        ]);
    }
}
