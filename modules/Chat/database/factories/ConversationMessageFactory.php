<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Inbox\Inbox;

class ConversationMessageFactory extends Factory
{
    protected $model = ConversationMessage::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'inbox_id' => Inbox::factory(),
            'conversation_id' => Conversation::factory(),
            'sender_type' => 'Modules\Chat\Models\Customers\Customer',
            'sender_id' => 1,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => fake()->sentence(),
            'source_id' => fake()->uuid(),
            'status' => 'sent',
            'private' => false,
        ];
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'outgoing',
            'sender_type' => 'App\Models\User',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
