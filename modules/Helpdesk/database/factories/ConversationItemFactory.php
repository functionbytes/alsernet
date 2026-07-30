<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationItemFactory extends Factory
{
    protected $model = ConversationItem::class;

    public function definition(): array
    {
        return [
            'conversation_id' => ConversationFactory::new(),
            'author_id' => null,
            'user_id' => null,
            'type' => 'message',
            'body' => fake()->paragraph(),
            'html_body' => null,
            'attachment_urls' => null,
            'is_internal' => false,
            'metadata' => null,
            'external_id' => null,
        ];
    }

    public function fromCustomer(int $customerId): static
    {
        return $this->state([
            'author_id' => $customerId,
            'user_id' => null,
        ]);
    }

    public function fromAgent(int $userId): static
    {
        return $this->state([
            'author_id' => null,
            'user_id' => $userId,
        ]);
    }

    public function internal(): static
    {
        return $this->state([
            'is_internal' => true,
            'user_id' => 1,
        ]);
    }

    public function systemEvent(string $eventType = 'status_change'): static
    {
        return $this->state([
            'type' => $eventType,
            'body' => fake()->sentence(),
            'author_id' => null,
            'user_id' => null,
        ]);
    }
}
