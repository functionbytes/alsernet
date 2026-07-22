<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;

class TicketSlaPolicyFactory extends Factory
{
    protected $model = TicketSlaPolicy::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Básico', 'Estándar', 'Premium', 'Enterprise']).' '.fake()->numberBetween(1, 9),
            'description' => fake()->optional()->sentence(),
            'channel' => null,
            'first_response_time' => fake()->randomElement([30, 60, 120, 240, 480]),
            'next_response_time' => fake()->randomElement([60, 120, 240, 480, 1440]),
            'resolution_time' => fake()->randomElement([480, 1440, 2880, 4320, 10080]),
            'business_hours_only' => false,
            'business_hours' => null,
            'timezone' => 'America/Bogota',
            'priority_multipliers' => [
                'urgent' => 0.25,
                'high' => 0.5,
                'normal' => 1.0,
                'low' => 2.0,
            ],
            'enable_escalation' => false,
            'escalation_threshold_percent' => 80,
            'escalation_recipients' => [],
            'active' => true,
            'is_default' => false,
        ];
    }

    /**
     * Default SLA policy (only one should exist).
     */
    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    /**
     * Strict SLA with business hours only.
     */
    public function businessHours(): static
    {
        return $this->state([
            'business_hours_only' => true,
            'business_hours' => [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
            ],
        ]);
    }

    /**
     * Inactive SLA policy.
     */
    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    /**
     * Policy scoped to a ticket source channel (widget, email, web_form...).
     */
    public function channel(string $channel): static
    {
        return $this->state(['channel' => $channel]);
    }
}
