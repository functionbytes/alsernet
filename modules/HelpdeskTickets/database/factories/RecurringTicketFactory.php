<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\RecurringTicket;

class RecurringTicketFactory extends Factory
{
    protected $model = RecurringTicket::class;

    public function definition(): array
    {
        $frequency = fake()->randomElement(['daily', 'weekly', 'monthly', 'custom']);

        return [
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'category_id' => null,
            'priority_id' => null,
            'assignee_id' => null,
            'frequency' => $frequency,
            'cron_expression' => $frequency === 'custom' ? '0 9 * * 1' : null,
            'next_run_at' => now()->addDay(),
            'last_run_at' => null,
            'is_active' => true,
            'tickets_created' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function daily(): static
    {
        return $this->state(['frequency' => 'daily', 'next_run_at' => now()->addDay()]);
    }

    public function weekly(): static
    {
        return $this->state(['frequency' => 'weekly', 'next_run_at' => now()->addWeek()]);
    }

    public function dueToRun(): static
    {
        return $this->state([
            'is_active' => true,
            'next_run_at' => now()->subMinute(),
        ]);
    }
}
