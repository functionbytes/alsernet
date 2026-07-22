<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\Reminder;

class ReminderFactory extends Factory
{
    protected $model = Reminder::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'conversation_id' => null,
            'title' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(),
            'remind_at' => now()->addHour(),
            'email_notify' => fake()->boolean(),
            'notified_at' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(['completed_at' => now()]);
    }

    public function notified(): static
    {
        return $this->state(['notified_at' => now()]);
    }
}
