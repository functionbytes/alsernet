<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Inbox\Inbox;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'inbox_id' => Inbox::factory(),
            'customer_id' => Customer::factory(),
            'assignee_id' => null,
            'team_id' => null,
            'status_id' => null,
            'priority_id' => null,
            'subject' => fake()->sentence(),
            'is_archived' => false,
            'custom_attributes' => [],
            'cached_label_list' => null,
            'first_response_at' => null,
            'assigned_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'first_response_sla_breached' => false,
            'resolution_sla_breached' => false,
            'last_activity_at' => now(),
            'last_message_at' => now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
        ]);
    }

    public function assigned($userId): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => $userId,
            'assigned_at' => now(),
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => null,
            'assigned_at' => null,
        ]);
    }
}
