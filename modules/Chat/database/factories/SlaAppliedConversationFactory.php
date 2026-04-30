<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Sla\SlaAppliedConversation;
use Modules\Chat\Models\Sla\SlaPolicy;

class SlaAppliedConversationFactory extends Factory
{
    protected $model = SlaAppliedConversation::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sla_id' => SlaPolicy::factory(),
            'first_response_due_at' => now()->addHour(),
            'first_response_at' => null,
            'first_response_breached' => false,
            'resolution_due_at' => now()->addHours(4),
            'resolved_at' => null,
            'resolution_breached' => false,
        ];
    }

    public function firstResponseBreached(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_response_breached' => true,
            'first_response_due_at' => now()->subHour(),
        ]);
    }

    public function resolutionBreached(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolution_breached' => true,
            'resolution_due_at' => now()->subHours(2),
        ]);
    }

    public function approaching(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_response_due_at' => now()->addMinutes(15),
            'resolution_due_at' => now()->addMinutes(30),
        ]);
    }
}
